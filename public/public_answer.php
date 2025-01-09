<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    public/public_answer.php
 * \ingroup digiquali
 * \brief   Public page to questions answer
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Get module parameters
$objectType   = GETPOST('object_type', 'alpha');
$documentType = GETPOST('document_type', 'alpha');

// Load Saturne libraries
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') || getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_WORKFLOW')) {
    require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
}

// Load DigiQuali libraries
require_once __DIR__ . '/../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/answer.class.php';
require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../lib/digiquali_answer.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $moduleNameLowerCase, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$trackID   = GETPOST('track_id', 'alpha');
$entity    = GETPOST('entity');
$action    = GETPOST('action');
$subaction = GETPOST('subaction');

// Initialize technical objects
$className  = ucfirst($objectType);
$object     = new $className($db);
$className  = $className . 'Line';
$objectLine = new $className($db);
$sheet      = new Sheet($db);
$question   = new Question($db);
$answer     = new Answer($db);
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY')) {
    $fileExists = file_exists('../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php');
    if ($fileExists && GETPOSTISSET('document_type')) {
        require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php';
    }
    if (GETPOSTISSET('document_type') && $fileExists) {
        $document = new $documentType($db);
    }
    $signatory = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

    $upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1];
}

$hookmanager->initHooks(['publicanswer', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Load object
$object->fetch(0, '', ' AND track_id = ' . "'" . $trackID . "'");
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY')) {
    $signatory->fetch(0, '', ' AND status >= ' . SaturneSignature::STATUS_REGISTERED . ' AND object_type= ' . "'" . $object->element . "'" . ' AND fk_object = ' . "'" . $object->id . "'");
}

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0) {
        // Actions add_signature, builddoc, remove_file
        require_once __DIR__ . '/../../saturne/core/tpl/actions/signature_actions.tpl.php';
    }

    // Set user for action update and insert for prevent error on public interface
    $user->id = 1;

    require_once __DIR__ . '/../core/tpl/digiquali_answers_save_action.tpl.php';
}

/*
 * View
 */

$title  = $langs->trans('PublicAnswer');
$moreJS = ['/saturne/js/includes/signature-pad.min.js', '/saturne/js/includes/hammer.min.js'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(1,'', $title, '', '', 0, 0, $moreJS, [], '', 'page-public-card page-signature');

print '<div class="wpeo-gridlayout grid-4">';
$logosPath = DOL_DATA_ROOT . ($conf->entity > 1 ? '/' . $conf->entity . '/' : '/') . 'mycompany/logos/thumbs/';
$logosList = dol_dir_list($logosPath);
if (is_array($logosList) && !empty($logosList)) {
    $logo = array_pop($logosList);
    $logoSrc = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/thumbs/' . $logo['name']);
} else {
    $logoSrc = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
}

print '<div class="card" style="height: 200px">';
print '<br>';
print '<img src="' . $logoSrc . '" alt="SocietyLogo" style="width:40%">';
print '</div>';
print '</div>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=save&id=' . $object->id . '&track_id=' . $trackID . '&object_type=' . $object->element . '&document_type=' . $documentType . '&submit=1&entity=' . $conf->entity . '" id="saveObject" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="public_interface" value="true">';
print '<input type="hidden" name="action" value="save">';

print '<div id="tablelines" class="question-answer-container public-card__container" data-public-interface="true" style="max-width: 1000px; margin-bottom: 4em;">';
$substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
complete_substitutions_array($substitutionArray, $langs, $object);
$answerPublicInterfaceTitle = make_substitutions($langs->transnoentities($conf->global->DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE), $substitutionArray);
print '<h2 class="center">' . (dol_strlen($answerPublicInterfaceTitle) > 0 ? $answerPublicInterfaceTitle : $langs->transnoentities('AnswerPublicInterface')) . '</h2>';
print '<br>';
$publicInterface = true;
$sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_' . $sheet->element, null, '', 'OR', 1, 'position');
$questionCount = (is_array($sheet->linkedObjects['digiquali_question']) && !empty($sheet->linkedObjects['digiquali_question']) ? count($sheet->linkedObjects['digiquali_question']) : 0);
print '<div class="badge badge-status8">' . $langs->trans('NbQuestions') . ': ' . $questionCount . '</div><div><br></div>';
require_once __DIR__ . '/../core/tpl/digiquali_answers.tpl.php';
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0) {
    $previousStatus        = $object->status;
    $object->status        = $object::STATUS_VALIDATED; // Special case because public answer need draft status object to complete question
    $moreParams['moreCSS'] = 'hidden';                  // Needed for prevent click on signature button action
    print '<div style="margin-top: 2em;">';
    require_once __DIR__ . '/../../saturne/core/tpl/signature/public_signature_view.tpl.php';
    print '</div>';
    $object->status = $previousStatus;
}
print '<div class="public-card__footer center" style="margin-top: 2em;">';
if ($object->status == $object::STATUS_DRAFT && !getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_WORKFLOW')) {
    print '<button type="submit" class="wpeo-button save-public-answer ' . (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0 ? 'signature-validate button-disable' : '') . '">' . $langs->trans('Submit') . '</button>';
    print '</div>';
} else if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_WORKFLOW')) {
    $submit = GETPOST('submit', 'int');

    if ($submit == 0 && $object->status == $object::STATUS_DRAFT) {
        print '<button type="submit" class="wpeo-button save-public-answer ' . (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0 ? 'signature-validate button-disable' : '') . '">' . $langs->trans('Submit') . '</button>';
    }
    print '</form>';
    if ($submit == 1) {
        print '<div class="inline-block"><a class="wpeo-button" style="margin-right: 2em;" href="' . $_SERVER['PHP_SELF'] . '?action=save&cancel_action=true&id=' . $object->id . '&track_id=' . $trackID . '&object_type=' . $object->element . '&document_type=' . $documentType . '&submit=0&entity=' . $conf->entity . '">' . $langs->trans('Modify') . '</a></div>';
        print '<div class="inline-block"><a class="wpeo-button" href="' . $_SERVER['PHP_SELF'] . '?action=save&public_interface=true&id=' . $object->id . '&track_id=' . $trackID . '&object_type=' . $object->element . '&document_type=' . $documentType . '&entity=' . $conf->entity . '">' . $langs->trans('Save') . '</a></div>';
    }
    print '</div>';
}
print '</div>';
print '</form>';

llxFooter('', 'public');
$db->close();
