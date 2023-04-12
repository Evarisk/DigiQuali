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
 *  \brief      Tab for medias on Control
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
require_once __DIR__ . '/../../lib/dolismq_function.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/controlequipment/mod_control_equipment_standard.php';
require_once __DIR__ . '/../../../saturne/lib/object.lib.php';

// Global variables definitions
global $conf, $db,$hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["productbatch"]);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objectsp
$object                 = new Control($db);
$extrafields            = new ExtraFields($db);
$controlEquipment       = new ControlEquipment($db);
$equipment              = new Product($db);
$refControlEquipmentMod = new $conf->global->DOLISMQ_CONTROL_EQUIPMENT_ADDON($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('controlequipment', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->dolismq->multidir_output[$object->entity]."/".$object->id;
}

$permissiontoread   = $user->rights->dolismq->control->read;
$permissiontoadd    = $user->rights->dolismq->control->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->control->delete || ($permissiontoadd && isset($object->status));
$upload_dir = $conf->dolismq->multidir_output[$conf->entity];

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
if ($user->socid > 0) $socid = $user->socid;
if (empty($conf->dolismq->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 * Action
 */

if (empty($reshook)) {
// Action to add or link equipment to control
	if ($action == 'add_equipment' && $permissiontoadd) {
		$equipmentId = GETPOST('equipmentId');

		if ($equipmentId > 0) {
			$equipmentsControl = $controlEquipment->fetchFromParent($object->id);

			$equipment->fetch($equipmentId);

			$controlEquipment->ref = $refControlEquipmentMod->getNextValue($controlEquipment);
			$controlEquipment->fk_product = $equipment->id;
			$controlEquipment->fk_control = $object->id;

			$result = $controlEquipment->insert($user);
			if ($result > 0) {
				setEventMessages($langs->trans('AddEquipmentLink') . ' ' . $controlEquipment->ref, array());
			} else {
				setEventMessages($langs->trans('ErrorEquipmentLink'), null, 'errors');
			}
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
			exit;
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), null, 'errors');
		}
	}

// Action to unlink equipment from control
	if ($action == 'unlink_equipment' && $permissiontodelete) {
		$equipmentId = GETPOST('equipmentId');

		if ($equipmentId > 0) {
			$equipmentsControl = $controlEquipment->fetchFromParent($object->id);

			foreach ($equipmentsControl as $equipmentControl) {
				if ($equipmentId == $equipmentControl->fk_product && $equipmentControl->status != 0) {
					$result = $equipmentControl->delete($user);
					break;
				}
			}

			if ($result > 0) {
				setEventMessages($langs->trans('UnlinkEquipmentLink') . ' ' . $equipmentControl->ref, array());
			}
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), null, 'errors');
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

	$products     = saturne_fetch_all_object_type('Product');
	$productsData = [];
	if (is_array($products) && !empty($products)) {
		foreach ($products as $key => $value) {
			$productsData[$value->id]= $value->label;
		}
	}
	$equipmentIds      = [];
	$equipmentsControl = $controlEquipment->fetchFromParent($object->id);
	if (is_array($equipmentsControl) && !empty ($equipmentsControl)) {
		foreach ($equipmentsControl as $equipmentControl) {
			if ($equipmentControl->status == 0) continue;
			$equipment->fetch($equipmentControl->fk_product);
			$equipmentIds[$equipment->id] = $equipment->label;
		}
	}
	$selectArray = array_diff($productsData, $equipmentIds);

	if (is_array($equipmentIds) && !empty($equipmentIds)) {
		ksort($equipmentIds);
	}

	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans("ControlEquipmentList"), '', '');
	print '<table id="tablelines" class="centpercent noborder noshadow">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;
	// Lines
	print '<thead><tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('Label') . '</td>';
	print '<td>' . $langs->trans('EatByDate') . '</td>';
	print '<td class="center">' . $langs->trans('Action') . '</td>';
	print '<td> </td>';
	print '</tr></thead>';

	if (is_array($equipmentIds) && !empty($equipmentIds)) {
		print '<tbody><tr>';
		foreach ($equipmentIds as $equipmentId => $label) {
			$item = $equipment;
			$item->fetch($equipmentId);

			print '<tr id="'. $item->id .'" class="line-row oddeven">';
			print '<td>';
			print $item->getNomUrl(1);
			print '</td>';

			print '<td>';
			print $item->label;
			print '</td>';

			print '<td>';
			print $item->lifetime ? $item->lifetime . ' ' . $langs->trans("Days") : $langs->trans('NoData');
			print '</td>';

			print '<td class="center">';
			if ($object->status != 2) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=unlink_equipment&equipmentId=' . $equipmentId . '">';
				print img_delete();
				print '</a>';
			}
			print '</td>';
			print '<td>';
			print '</td>';
			print '</tr>';
			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
		}
		print '</tr></tbody>';
	}

	if ($object->status != 2) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="add_equipment">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<tr class="add-line"><td class="">';
		print $form->selectarray('equipmentId', $selectArray, 'ifone', $langs->trans('SelectControlEquipment'));
		print '</td>';
		print '<td>';
		print ' &nbsp; <input type="submit" id ="add_equipment" class="button" name="add_equipment" value="' . $langs->trans("Add") . '">';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
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
