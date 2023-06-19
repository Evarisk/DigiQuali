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
 *  \file       view/control/control_medias.php
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
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';

// Global variables definitions
global $conf, $db,$hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["companies"]);

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

$hookmanager->initHooks(array('controlmedia', 'globalcard')); // Note that conf->hooks_modules contains array

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
$title    = $langs->trans('Medias');

saturne_header(0,'', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = control_prepare_head($object);

	print saturne_get_fiche_head($object, 'medias', $title);

	// Object card
	// ------------------------------------------------------------

    saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

	print '<div class="fichecenter control-list-medias">';
	print '<div class="underbanner clearboth"></div>';

	print load_fiche_titre($langs->trans('MediaGalleryQuestionAnswers'), '', '');

	$object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');
	$questionIds     = $object->linkedObjectsIds;
	$questionsLinked = $object->linkedObjects;
	$relativepath    = 'dolismq/medias/thumbs';
	$linkedMedias    = 0;

	if (is_array($questionsLinked['dolismq_question']) && !empty($questionsLinked['dolismq_question'])) {
		foreach ($questionsLinked['dolismq_question'] as $questionLinked) {
			if ($questionLinked->authorize_answer_photo > 0 && file_exists($conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $questionLinked->ref)) {
				print '<div class="question-section">';
				print '<span class="question-ref">' . $questionLinked->ref . '</span>';
				print '<div class="table-cell table-full linked-medias answer_photo">';
				print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $questionLinked->ref, ($conf->global->DOLISMQ_CONTROL_USE_LARGE_MEDIA_IN_GALLERY ? 'large' : 'medium'), '', 0, 0, 0, 200, 200, 0, 0, 0, 'control/' . $object->ref . '/answer_photo/' . $questionLinked->ref, null, '', 0, 0);
				print '</div>';
				print '</div>';
				$linkedMedias++;
			}
		}
	}

	if ($linkedMedias == 0) {
		print $langs->trans('NoControlAnswersPhoto');
	}

	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
