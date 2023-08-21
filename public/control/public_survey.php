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
 * \file    public/control/public_survey.php
 * \ingroup digiquali
 * \brief   Public page to answer control questions.
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged.
	define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website.
	define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip.
	define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load DigiQuali environment.
if (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load DigiQuali libraries.
require_once __DIR__ . '/../../../digiquali/class/control.class.php';
require_once __DIR__ . '/../../../digiquali/class/sheet.class.php';
require_once __DIR__ . '/../../../digiquali/class/question.class.php';
require_once __DIR__ . '/../../../digiquali/class/answer.class.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_answer.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page.
saturne_load_langs();

// Get parameters.
$track_id  = GETPOST('track_id', 'alpha');
$entity    = GETPOST('entity');
$action    = GETPOST('action');
$subaction = GETPOST('subaction');

// Initialize technical objects.
$object           = new Control($db);
$controldet       = new ControlLine($db);
$question         = new Question($db);
$answer           = new Answer($db);
$sheet            = new Sheet($db);
$user             = new User($db);

$hookmanager->initHooks(['publicsurvey']); // Note that conf->hooks_modules contains array.

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Load object.
$object->fetch(0, '', ' AND track_id =' . "'" . $track_id . "'");

/*
 * Actions
*/

require_once __DIR__ . '/../../core/tpl/digiquali_control_answers_save_action.tpl.php';

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(1, '', $title);

if ($action == 'saved_success' || $object->status > $object::STATUS_DRAFT) {
	print '<div class="signature-container" width="100%" style="max-width: 1000px;">';
	print '<div class="center">' . $langs->trans('YourAnswersHaveBeenSaved') . '</div>';
	print '</div>';
} else {
	$sheet->fetchQuestionsLinked($object->fk_sheet, 'digiquali_' . $sheet->element);
	$questionIds = $sheet->linkedObjectsIds['digiquali_question'];

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?action=save&id='.$object->id.'&track_id='. GETPOST('track_id') .'" id="saveControl" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="save">';

	?>
	<div id="tablelines" class="control-audit signature-container" width="100%" style="max-width: 1000px;">
		<?php
			print '<h2 class="center"><b>' . $conf->global->DIGIQUALI_PUBLIC_SURVEY_TITLE . '</b></h2>';
            print '<br>';
			require_once __DIR__ . '/../../core/tpl/digiquali_control_answers.tpl.php';
            print '<br>';
            print '<div class="center">';
			print '<input class="wpeo-button" type="submit" value="'. $langs->trans('Submit') .'">';
			print '</div>';
		?>
	</div>
	<?php
	print '</form>';
}


llxFooter('', 'public');
$db->close();
