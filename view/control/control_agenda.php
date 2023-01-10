<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 *  \file       view/control/control_agenda.php
 *  \ingroup    dolismq
 *  \brief      Page of Control events
 */

// Load Dolibarr environment
if (file_exists('../../../../main.inc.php')) {
	require_once '../../../../main.inc.php';
} else {
	die('Include of main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
$langs->loadLangs(['dolismq@dolismq', 'other']);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST('actioncode', 'alpha', 3) ? GETPOST('actioncode', 'alpha', 3) : (GETPOST('actioncode') == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}

$search_agenda_label = GETPOST('search_agenda_label');

$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');
if (empty($page) || $page == -1) { // If $page is not defined, or '' or -1
	$page = 0;
}
$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Initialize technical objects
$object      = new Control($db);
$extrafields = new ExtraFields($db);
$project     = new Project($db);

$hookmanager->initHooks(['controlagenda', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || ! empty($ref)) {
	$upload_dir = $conf->dolismq->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . '/' . $object->id;
}

// Security check - Protection if external user
$permissiontoread = $user->rights->dolismq->control->read;
if (empty($conf->dolismq->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 *  Actions
 */

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && ! empty($backtopage)) {
		header('Location: ' . $backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode          = '';
		$search_agenda_label = '';
	}
}

/*
 *	View
 */

if ($object->id > 0) {
	$title    = $langs->trans('Control') . ' - ' . $langs->trans('Agenda');
	$help_url = 'FR:Module_DoliSMQ';
	$morejs   = ['/dolismq/js/dolismq.js'];
	$morecss  = ['/dolismq/css/dolismq.css'];

	llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

	$head = controlPrepareHead($object);

	print dol_get_fiche_head($head, 'controlAgenda', $title, -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/dolismq/view/control/control_list.php', 1).'">' . $langs->trans('BackToList') . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Project
	if (!empty($conf->projet->enabled)) {
		$langs->load('projects');
		if (!empty($object->projectid)) {
			$project->fetch($object->projectid);
			$morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1);
		} else {
			$morehtmlref .= '';
		}
	}
	$morehtmlref .= '</div>';

	$object->picto = 'control_small@dolismq';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();

	// Actions buttons
	$out = '&origin=' . urlencode($object->element.'@'.$object->module) . '&originid=' . urlencode($object->id);
	$urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);

	print '<div class="tabsAction">';
	if (!empty($conf->agenda->enabled)) {
		if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
			print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '">' . $langs->trans('AddAction') . '</a>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('PermissionDenied')) . '">'. $langs->trans('AddAction') . '</span>';
		}
	}
	print '</div>';

	if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
		$param = '&id=' . $object->id;
		if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
			$param .= '&contextpage=' . urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit=' . urlencode($limit);
		}

		print load_fiche_titre($langs->trans('ActionsOnControl'), '', '');

		// List of all actions
		$filters = [];
		$filters['search_agenda_label'] = $search_agenda_label;

		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
	}
}

// End of page
llxFooter();
$db->close();
