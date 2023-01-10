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
 *  \file       view/control/control_note.php
 *  \ingroup    dolismq
 *  \brief      Tab for notes on Control
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';

// Global variables definitions
global $conf, $db,$hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "companies"));

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize objects
// Technical objets
$object = new Control($db);
$extrafields = new ExtraFields($db);
$project     = new Project($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('controlnote', 'globalcard')); // Note that conf->hooks_modules contains array

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
$permissionnote     = $user->rights->dolismq->control->write; // Used by the include of actions_setnotes.inc.php
$upload_dir = $conf->dolismq->multidir_output[$conf->entity];

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
if ($user->socid > 0) $socid = $user->socid;
if (empty($conf->dolismq->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

/*
 * View
 */

$help_url = '';
llxHeader('', $langs->trans('Control'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = controlPrepareHead($object);

	print dol_get_fiche_head($head, 'note', $langs->trans('Notes'), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/dolismq/control_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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

	$cssclass = "titlefield";

	if ($object->status == 2) {
		$permissionnote = 0;
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
