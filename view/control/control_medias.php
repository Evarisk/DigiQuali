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
 *  \file       view/control/control_medias.php
 *  \ingroup    dolismq
 *  \brief      Tab for medias on Control
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
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';
require_once __DIR__ . '/../../lib/dolismq_function.lib.php';

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

// Initialize technical objects
$object      = new Control($db);
$controldet  = new ControlLine($db);
$question    = new Question($db);
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
 * View
 */

$help_url = '';
$morecss  = array('/dolismq/css/dolismq.css');
llxHeader('', $langs->trans('Control'), $help_url, '', 0, 0, '', $morecss);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = controlPrepareHead($object);

	print dol_get_fiche_head($head, 'controlMedias', $langs->trans('Medias'), -1, $object->picto);

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

	print '<div class="fichecenter control-list-medias">';
	print '<div class="underbanner clearboth"></div>';

	print load_fiche_titre($langs->trans('MediaGalleryQuestionAnswers'), '', '');

	$object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');
	$questionIds = $object->linkedObjectsIds;

	if (!empty($questionIds['dolismq_question']) && $questionIds > 0) {
		foreach ($questionIds['dolismq_question'] as $questionId) {
			$question->fetch($questionId);
			if ($question->authorize_answer_photo > 0 && file_exists($conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $question->ref)) {
				print '<div class="question-section">';
				print '<span class="question-ref">' . $question->ref . '</span>';
				print '<div class="table-cell table-full linked-medias answer_photo">';
				$relativepath = 'dolismq/medias/thumbs';
				print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $question->ref, ($conf->global->DOLISMQ_CONTROL_USE_LARGE_MEDIA_IN_GALLERY ? 'large' : 'medium'), '', 0, 0, 0, 200, 200, 0, 0, 0, 'control/' . $object->ref . '/answer_photo/' . $question->ref, null, (GETPOST('favorite_answer_photo') ? GETPOST('favorite_answer_photo') : $questionControlDet->answer_photo), 0, 0);
				print '</div>';
				print '</div>';
			}
		}
	}

	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
