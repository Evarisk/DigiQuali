<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       view/control/control_equipment.php
 *  \ingroup    dolismq
 *  \brief      Tab for equipment on Control
 */

// Load DoliSMQ environment
if (file_exists('../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../dolismq.main.inc.php';
} elseif (file_exists('../../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/controlequipment/mod_control_equipment_standard.php';
require_once __DIR__ . '/../../../saturne/lib/object.lib.php';

// Global variables definitions
global $conf, $db,$hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['productbatch']);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object                 = new Control($db);
$controlEquipment       = new ControlEquipment($db);
$equipment              = new Product($db);
$refControlEquipmentMod = new $conf->global->DOLISMQ_CONTROL_EQUIPMENT_ADDON($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(array('controlequipment', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';

$permissiontoread   = $user->rights->dolismq->control->read;
$permissiontoadd    = $user->rights->dolismq->control->write;
$permissiontodelete = $user->rights->dolismq->control->delete || ($permissiontoadd && isset($object->status));

// Security check (enable the most restrictive one)
saturne_check_access($permissiontoread, $object);

/*
 * Action
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Action to add or link equipment to control
	if ($action == 'add_equipment' && $permissiontoadd) {
		$equipmentId = GETPOST('equipmentId');

		if ($equipmentId > 0) {
			$equipment->fetch($equipmentId);

			$controlEquipment->ref        = $refControlEquipmentMod->getNextValue($controlEquipment);
			$controlEquipment->fk_product = $equipment->id;
			$controlEquipment->fk_control = $object->id;

			$result = $controlEquipment->insert($user);
			if ($result > 0) {
				setEventMessages($langs->trans('AddEquipmentLink') . ' ' . $controlEquipment->ref, []);
			} else {
				setEventMessages($langs->trans('ErrorEquipmentLink'), [], 'errors');
			}
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $id);
			exit;
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), [], 'errors');
		}
	}

	// Action to unlink equipment from control
	if ($action == 'unlink_equipment' && $permissiontodelete) {
		$equipmentId = GETPOST('equipmentId');

		if ($equipmentId > 0) {
			$equipmentsControl = $controlEquipment->fetchFromParent($object->id);

			if (is_array($equipmentsControl) && !empty($equipmentsControl)) {
				foreach ($equipmentsControl as $equipmentControl) {
					if ($equipmentId == $equipmentControl->fk_product && $equipmentControl->status != $equipmentControl::STATUS_DELETED) {
						$result = $equipmentControl->delete($user);
						break;
					}
				}
			}

			if ($result > 0) {
				setEventMessages($langs->trans('UnlinkEquipmentLink') . ' ' . $equipmentControl->ref, []);
			}
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), [], 'errors');
		}
	}
}

/*
 * View
 */

$help_url = '';
$morecss  = array('/dolismq/css/dolismq.css');
$morejs  = array('/dolismq/js/dolismq.js');
saturne_header(0,'', $langs->trans('Control'), $help_url, '', 0, 0, $morejs, $morecss);

if ($id > 0 || !empty($ref)) {
	// CONTROL EQUIPMENT LINES
	print saturne_get_fiche_head($object, 'equipment', $langs->trans('Equipment'));
	saturne_banner_tab($object);

	$equipmentIds      = [];
	$equipmentsControl = $controlEquipment->fetchFromParent($object->id);
	if (is_array($equipmentsControl) && !empty ($equipmentsControl)) {
		foreach ($equipmentsControl as $equipmentControl) {
			if ($equipmentControl->status == 0) continue;
			$equipment->fetch($equipmentControl->fk_product);
			$excludeFilter .= $equipmentControl->fk_product . ',';
			$equipmentIds[$equipment->id] = $equipmentControl->ref;
		}
	}

	$excludeFilter = !empty($excludeFilter) ? substr($excludeFilter, 0, -1) : 0;
	$products      = saturne_fetch_all_object_type('Product', '', '', 0, 0, ['customsql' => '`rowid` NOT IN (' . $excludeFilter . ')']);
	$productsData  = [];
	if (is_array($products) && !empty($products)) {
		foreach ($products as $key => $value) {
			$productsData[$value->id] = $value->label;
		}
	}

	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans("ControlEquipmentList"), '', '');
	print '<table id="tablelines" class="centpercent noborder noshadow">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;
	// Lines
	print '<thead><tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('ProductRef') . '</td>';
	print '<td>' . $langs->trans('Label') . '</td>';
	print '<td class="center">' . $langs->trans('OptimalExpirationDate');
	print $form->textwithpicto('', $langs->trans('OptimalExpirationDateDescription')) . '</td>';
	print '<td class="center">' . $langs->trans('RemainingDays') . '</td>';
	print '<td class="center">' . $langs->trans('Action') . '</td>';
	print '<td> </td>';
	print '</tr></thead>';

	if (is_array($equipmentIds) && !empty($equipmentIds)) {
		print '<tbody><tr>';
		foreach ($equipmentIds as $equipmentId => $ref) {
			$item = $equipment;
			$item->fetch($equipmentId);

			print '<tr id="'. $item->id .'" class="line-row oddeven">';
			print '<td>';
			print $ref;
			print '</td>';

			print '<td>';
			print $item->getNomUrl(1);
			print '</td>';

			print '<td>';
			print $item->label;
			print '</td>';

			print '<td class="center">';
			$creationDate   = strtotime($item->date_creation);
			$expirationDate = dol_time_plus_duree($creationDate, $item->lifetime, 'd');
			print  $item->lifetime ? dol_print_date($expirationDate, 'day') : $langs->trans('NoData');
			print '</td>';

			print '<td class="center">';
			$remainingDay   = convertSecondToTime($expirationDate - dol_now(), 'allwithouthour')?: '- ' . convertSecondToTime(dol_now() - $expirationDate, 'allwithouthour');
			if (empty($item->lifetime) || $expirationDate <= dol_now()) {
				print '<span style="color:red">';
			} else if (!empty($item->lifetime) && $expirationDate <= dol_now() + 2592000) {
				print '<span style="color:orange">';
			} else {
				print '<span style="color:green">';
			}
			print  $item->lifetime ? $remainingDay : $langs->trans('NoData');
			print '</span> </td>';

			print '<td class="center">';
			if ($object->status != 2) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=unlink_equipment&equipmentId=' . $equipmentId . '">';
				print img_delete();
				print '</a>';
			}
			print '</td>';
			print '<td>';
			print '</td>';
			print '</tr>';
		}
		print '</tr></tbody>';
	}

	if ($object->status != 2) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="add_equipment">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<tr><td>';
		print $form->selectarray('equipmentId', $productsData, '', $langs->transnoentities('SelectControlEquipment'));
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td class="center">';
		print '<input type="submit" id ="add_equipment" class="button" name="add_equipment" value="' . $langs->trans('Add') . '">';
		print '</td>';
		print '<td>';
		print '</td>';
		print '</tr>';
		print '</form>';
	}

	print '</table>';
	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
