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
 * \file    view/sheet/sheet_export.php
 * \ingroup digiquali
 * \brief   Page to export sheet and linked questions on sheet
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object   = new Sheet($db);
$question = new Question($db);
$answer   = new Answer($db);

$hookmanager->initHooks(['sheetexport', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

$upload_dir = $conf->digiquali->multidir_output[$conf->entity ?? 1];

// Security check - Protection if external user
$permissionToRead = $user->hasRight('digiquali', 'sheet', 'read');
$permissionToAdd  = $user->hasRight('digiquali', 'sheet', 'write');
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'export_sheet' && $permissionToAdd) {
        $digiqualiExportArray = [];
        $sheetExportArray['rowid']               = $object->id;
        $sheetExportArray['ref']                 = $object->ref;
        $sheetExportArray['status']              = $object->status;
        $sheetExportArray['type']                = $object->type;
        $sheetExportArray['label']               = $object->label;
        $sheetExportArray['description']         = $object->description;
        $sheetExportArray['element_linked']      = $object->element_linked;
        $sheetExportArray['success_rate']        = $object->success_rate;
        $sheetExportArray['mandatory_questions'] = $object->mandatory_questions;

        $digiqualiExportArray['sheets'][$object->id] = $sheetExportArray;

        $object->fetchObjectLinked($object->id, 'digiquali_' . $object->element, null, '', 'OR', 1, 'position');
        $questionsLinked = $object->linkedObjects['digiquali_question'];

        if (is_array($questionsLinked) && !empty($questionsLinked)) {
            foreach ($questionsLinked as $key => $questionSingle) {
                $digiqualiExportArray['element_element'][$object->id][$key] = $questionSingle->id;
                $questionExportArray['rowid']                               = $questionSingle->id;
                $questionExportArray['ref']                                 = $questionSingle->ref;
                $questionExportArray['status']                              = $questionSingle->status;
                $questionExportArray['type']                                = $questionSingle->type;
                $questionExportArray['label']                               = $questionSingle->label;
                $questionExportArray['description']                         = $questionSingle->description;
                $questionExportArray['show_photo']                          = $questionSingle->show_photo;
                $questionExportArray['authorize_answer_photo']              = $questionSingle->authorize_answer_photo;
                $questionExportArray['enter_comment']                       = $questionSingle->enter_comment;

                $digiqualiExportArray['questions'][$questionSingle->id] = $questionExportArray;

                $answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['fk_question' => $questionSingle->id]);

                if (is_array($answerList) && !empty($answerList)) {
                    foreach ($answerList as $answerSingle) {
                        $answerExportArray['rowid']       = $answerSingle->id;
                        $answerExportArray['ref']         = $answerSingle->ref;
                        $answerExportArray['status']      = $answerSingle->status;
                        $answerExportArray['value']       = $answerSingle->value;
                        $answerExportArray['position']    = $answerSingle->position;
                        $answerExportArray['pictogram']   = $answerSingle->pictogram;
                        $answerExportArray['color']       = $answerSingle->color;
                        $answerExportArray['fk_question'] = $answerSingle->fk_question;

                        $digiqualiExportArray['questions'][$answerSingle->fk_question]['answers'][$answerSingle->id] = $answerExportArray;
                    }
                }
            }
        }

        $fileDir    = $upload_dir . '/temp/';
        $exportName = (!empty($object->label) ? dol_string_nospecial($object->label, '_', '', ['-']) : $object->ref);
        $fileName   = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export';
        $fullName   = $fileDir . $fileName . '.json';

        if (!is_dir($fileDir)) {
            dol_mkdir($fileDir);
        }

        file_put_contents($fullName, json_encode($digiqualiExportArray));

        $zip = new ZipArchive();
        $zipFileName = $fileDir . $fileName . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($fullName, basename($fullName));
            $zip->close();

            $filepath = DOL_URL_ROOT . '/document.php?modulepart=digiquali&file=' . urlencode('temp/' . $fileName . '.zip');
            ?>
            <script>
                const alink = document.createElement('a');
                alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
                alink.setAttribute('download', <?php echo json_encode($fileName . '.zip'); ?>);
                alink.click();
            </script>
            <?php
            setEventMessages($langs->transnoentities('ExportWellDone'), []);
        } else {
            setEventMessages($langs->transnoentities('ExportFailed'), [], 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('Export', 'DigiQuali');
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $helpUrl);

saturne_get_fiche_head($object, 'export', $title);
saturne_banner_tab($object);

print load_fiche_titre($langs->trans('ExportSheetData'), '', '');

print '<form name="export_sheet_data" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="export_sheet">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ExportSheetData');
print '</td><td>';
print $langs->trans('ExportSheetDataDescription');
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button reposition" value="' . $langs->trans('ExportData') . '">';
print '</td></tr>';

print '</table>';
print '</form>';

print $langs->trans('ToImportDataGoToImportPage') . ' <a href="' . dol_buildpath('custom/digiquali/view/digiqualitools.php', 1) . '">' . $langs->trans('ClickHere') . '</a>';

// End of page
llxFooter();
$db->close();
