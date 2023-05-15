<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/dolismq_sheet.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Sheet
 */

/**
 * Prepare sheet pages header
 *
 * @param  CommonObject $object Object
 * @return array                Array of tabs
 * @throws Exception
 */
function sheet_prepare_head(CommonObject $object): array
{
	// Global variables definitions
	global $conf, $langs, $db, $user;

	// Load translation files required by the page
	saturne_load_langs();

	// Initialize values
	$h = 0;
	$head = [];

	$head[$h][0] = dol_buildpath('/dolismq/view/sheet/sheet_card.php', 1) . '?id=' . $object->id;
	$head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/dolismq/view/control/control_list.php', 1) . '?fromid=' . $object->id . '&fromtype=fk_sheet';
	$head[$h][1] = '<i class="fas fa-tasks pictofixedwidth"></i>' . $langs->trans('Controls');
	$head[$h][2] = 'control';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/saturne/view/saturne_note.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
		$head[$h][1] = '<i class="fas fa-comment pictofixedwidth"></i>' . $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->dolismq->dir_output . '/' . $object->element . '/' . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath('/saturne/view/saturne_document.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
	$head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
	$head[$h][1] = '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events');
	if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
		$nbEvent = 0;
		// Enable caching of session count actioncomm
		require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
		$cachekey = 'count_events_' . $object->element . '_' . $object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = 'SELECT COUNT(id) as nb';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
			$sql .= ' WHERE fk_element = ' . $object->id;
			$sql .=  " AND elementtype = '" . $object->element . '@dolismq' . "'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm ' . $db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120); // If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans('Agenda');
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbEvent . '</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, $object->element . '@dolismq');

	complete_head_from_modules($conf, $langs, $object, $head, $h, $object->element . '@dolismq', 'remove');

	return $head;
}

/**
 * Get list of objects which can be linked to a sheet
 *
 * @param  CommonObject $object Object
 * @return array                Array of tabs
 * @throws Exception
 */
function get_sheet_linkable_objects(): array
{
	global $conf, $hookmanager, $db;

	require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

	$linkableObjectTypes = [];

	if (isModEnabled('product')) {
		$linkableObjectTypes['product'] = [
			'langs'     => 'ProductOrService',
			'picto'     => 'product',
			'className' => 'Product',
			'nameField' => 'ref'
		];
	}

	if (isModEnabled('productbatch')) {
		$linkableObjectTypes['productlot'] = [
			'langs'       => 'Batch',
			'picto'       => 'lot',
			'className'   => 'ProductLot',
			'nameField'   => 'batch',
			'fk_parent'   => 'fk_product',
			'parent_post' => 'fk_product'
		];
	}

	if (isModEnabled('user')) {
		$linkableObjectTypes['user'] = [
			'langs'     => 'User',
			'picto'     => 'user',
			'className' => 'User',
			'nameField' => 'lastname, firstname'
		];
	}

	if (isModEnabled('societe')) {
		$linkableObjectTypes['thirdparty'] = [
			'langs'     => 'ThirdParty',
			'picto'     => 'building',
			'className' => 'Societe',
			'nameField' => 'nom'
		];
		$linkableObjectTypes['contact'] = [
			'langs'       => 'Contact',
			'picto'       => 'address',
			'className'   => 'Contact',
			'nameField'   => 'lastname, firstname',
			'fk_parent'   => 'fk_soc',
			'parent_post' => 'fk_thirdparty'
		];
	}

	if (isModEnabled('project')) {
		$linkableObjectTypes['project'] = [
			'langs'     => 'Project',
			'picto'     => 'project',
			'className' => 'Project',
			'nameField' => 'ref, title'
		];
		$linkableObjectTypes['task'] = [
			'langs'       => 'Task',
			'picto'       => 'projecttask',
			'className'   => 'SaturneTask',
			'nameField'   => 'label',
			'fk_parent'   => 'fk_projet',
			'parent_post' => 'fk_project'
		];
	}

	if (isModEnabled('facture')) {
		$linkableObjectTypes['invoice'] = [
			'langs'     => 'Invoice',
			'picto'     => 'bill',
			'className' => 'Facture',
			'nameField' => 'ref'
		];
	}

	if (isModEnabled('order')) {
		$linkableObjectTypes['order'] = [
			'langs'     => 'Order',
			'picto'     => 'order',
			'className' => 'Commande',
			'nameField' => 'ref'
		];
	}

	if (isModEnabled('contract')) {
		$linkableObjectTypes['contract'] = [
			'langs'     => 'Contract',
			'picto'     => 'contract',
			'className' => 'Contrat',
			'nameField' => 'ref'
		];
	}

	if (isModEnabled('ticket')) {
		$linkableObjectTypes['ticket'] = [
			'langs'     => 'Ticket',
			'picto'     => 'ticket',
			'className' => 'Ticket',
			'nameField' => 'ref, subject'
		];
	}

	//Hook to add controllable objects from other modules
	if ( ! is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}
	$hookmanager->initHooks(array('get_sheet_linkable_objects'));

	$reshook = $hookmanager->executeHooks('extendSheetLinkableObjectsList', $linkableObjectTypes);

	if ($reshook && (is_array($hookmanager->resArray) && !empty($hookmanager->resArray))) {
		$linkableObjectTypes = $hookmanager->resArray;
	}

	$linkableObjects = [];
	if (is_array($linkableObjectTypes) && !empty($linkableObjectTypes)) {
		foreach($linkableObjectTypes as $linkableObjectType => $linkableObjectInformations) {
			if ($linkableObjectType != 'context' && $linkableObjectType != 'currentcontext') {
				$confCode = 'DOLISMQ_SHEET_LINK_' . strtoupper($linkableObjectType);
				$linkableObjects[$linkableObjectType] = [
					'code'          => $confCode,
					'conf'          => $conf->global->$confCode,
					'name'          => 'Link' . ucfirst($linkableObjectType),
					'description'   => 'Link' . ucfirst($linkableObjectType) . 'Description',
					'langs'         => $linkableObjectInformations['langs'],
					'picto'         => $linkableObjectInformations['picto'],
					'className'     => $linkableObjectInformations['className'],
					'nameField'     => $linkableObjectInformations['nameField'],
					'fk_parent'     => $linkableObjectInformations['fk_parent'],
					'parent_post'   => $linkableObjectInformations['parent_post'],
				];
			}
		}
	}

	return $linkableObjects;
}
