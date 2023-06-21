<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       view/dolismqtools.php
 *	\ingroup    dolismq
 *	\brief      Tools page of dolismq left menu
 */

// Load DoliSMQ environment
if (file_exists('../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../class/answer.class.php';
require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../lib/dolismq.lib.php';

require_once __DIR__ . '/../core/modules/dolismq/answer/mod_answer_standard.php';
require_once __DIR__ . '/../core/modules/dolismq/question/mod_question_standard.php';
require_once __DIR__ . '/../core/modules/dolismq/sheet/mod_sheet_standard.php';


// Global variables definitions
global $conf, $db, $langs, $user;

saturne_load_langs();

$error          = 0;
$now            = dol_now();
$answer         = new Answer($db);
$question       = new Question($db);
$sheet          = new Sheet($db);
$refSheetMod    = new $conf->global->DOLISMQ_SHEET_ADDON($db);
$refQuestionMod = new $conf->global->DOLISMQ_QUESTION_ADDON($db);
$refAnswerMod   = new $conf->global->DOLISMQ_ANSWER_ADDON($db);

$upload_dir     = $conf->dolismq->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Security check
$permissionToReadQuestions   = $user->rights->dolismq->question->read;
$permissionToReadSheets      = $user->rights->dolismq->question->read;
$permissionToRead            = $permissionToReadQuestions && $permissionToReadSheets;
$permissionToImportQuestions = $user->rights->dolismq->question->write;
$permissionToImportSheets    = $user->rights->dolismq->sheet->write;
$permissionToWrite           = $permissionToImportQuestions && $permissionToImportSheets;

saturne_check_access($permissionToRead);

/*
 * Actions
 */

if (GETPOST('dataMigrationExportSQA', 'alpha') && $permissionToRead) {
    //DoliSMQ sheets data
    $allSheets = $sheet->fetchAll();
    if (is_array($allSheets) && !empty($allSheets)) {
        foreach ($allSheets as $sheetSingle) {
            $sheetExportArray['rowid'] = $sheetSingle->id;
            $sheetExportArray['ref'] = $sheetSingle->ref;
            $sheetExportArray['date_creation'] = $sheetSingle->date_creation;
            $sheetExportArray['date_modification'] = $sheetSingle->tms;
            $sheetExportArray['status'] = $sheetSingle->status;
            $sheetExportArray['label'] = $sheetSingle->label;
            $sheetExportArray['description'] = $sheetSingle->description;
            $sheetExportArray['element_linked'] = $sheetSingle->element_linked;
            //$sheetExportArray['mandatory_questions'] = $sheetSingle->mandatory_questions;

            $dolismqExportArray['sheets'][$sheetSingle->id] = $sheetExportArray;

            $sheetSingle->fetchQuestionsLinked($sheetSingle->id, 'dolismq_sheet');
            $questionsLinked = $sheetSingle->linkedObjectsIds['dolismq_question'];
            if (is_array($questionsLinked) && !empty($questionsLinked)) {
                ksort($questionsLinked);
                foreach ($questionsLinked as $questionId) {
                    $dolismqExportArray['element_element'][$sheetSingle->id][] = $questionId;
                }
            }
        }
    }

    $allQuestions = $question->fetchAll();
    if (is_array($allQuestions) && !empty($allQuestions)) {
        foreach ($allQuestions as $questionSingle) {
            $questionExportArray['rowid']                  = $questionSingle->id;
            $questionExportArray['ref']                    = $questionSingle->ref;
            $questionExportArray['date_creation']          = $questionSingle->date_creation;
            $questionExportArray['date_modification']      = $questionSingle->tms;
            $questionExportArray['status']                 = $questionSingle->status;
            $questionExportArray['type']                   = $questionSingle->type;
            $questionExportArray['label']                  = $questionSingle->label;
            $questionExportArray['description']            = $questionSingle->description;
            $questionExportArray['show_photo']             = $questionSingle->show_photo;
            $questionExportArray['authorize_answer_photo'] = $questionSingle->authorize_answer_photo;
            $questionExportArray['enter_comment']          = $questionSingle->enter_comment;
            $questionExportArray['photo_ok']               = $questionSingle->photo_ok;
            $questionExportArray['photo_ko']               = $questionSingle->photo_ko;

            $dolismqExportArray['questions'][$questionSingle->id] = $questionExportArray;

            $allAnswers = $answer->fetchAll();
            if (is_array($allAnswers) && !empty($allAnswers)) {
                foreach ($allAnswers as $answerSingle) {
                    if ($answerSingle->fk_question == $questionSingle->id) {
                        $answerExportArray['rowid']             = $answerSingle->id;
                        $answerExportArray['ref']               = $answerSingle->ref;
                        $answerExportArray['date_creation']     = $answerSingle->date_creation;
                        $answerExportArray['date_modification'] = $answerSingle->tms;
                        $answerExportArray['status']            = $answerSingle->status;
                        $answerExportArray['value']             = $answerSingle->value;
                        $answerExportArray['position']          = $answerSingle->position;
                        $answerExportArray['pictogram']         = $answerSingle->pictogram;
                        $answerExportArray['color']             = $answerSingle->color;
                        $answerExportArray['fk_question']       = $answerSingle->fk_question;

                        $dolismqExportArray['questions'][$questionSingle->id]['answers'][$answerSingle->id] = $answerExportArray;
                    }
                }
            }
        }
    }


    $dolismqExportArray = json_encode($dolismqExportArray, JSON_PRETTY_PRINT);

    $filedir = $upload_dir . '/temp/';
    $export_base = $filedir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_sheet_question_answer_export';
    $filename = $export_base . '.json';

    file_put_contents($filename, $dolismqExportArray);

    $zip = new ZipArchive();
    if ($zip->open($export_base . '.zip', ZipArchive::CREATE ) === TRUE) {
        $zip->addFile($filename, basename($filename));
        $zip->close();
        $filenamezip = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_sheet_question_answer_export.zip';
        $filepath = DOL_URL_ROOT . '/document.php?modulepart=dolismq&file=' . urlencode('temp/'.$filenamezip);

        ?>
        <script>
            var alink = document.createElement( 'a' );
            alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
            alink.setAttribute('download', <?php echo json_encode($filenamezip); ?>);
            alink.click();
        </script>
        <?php
        $fileExportGlobals = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
    }
}

if (GETPOST('dataMigrationExportQA', 'alpha') && $permissionToRead) {
    //DoliSMQ questions data
    $allQuestions = $question->fetchAll();
    if (is_array($allQuestions) && !empty($allQuestions)) {
        foreach ($allQuestions as $questionSingle) {
            $questionExportArray['rowid']                  = $questionSingle->id;
            $questionExportArray['ref']                    = $questionSingle->ref;
            $questionExportArray['date_creation']          = $questionSingle->date_creation;
            $questionExportArray['date_modification']      = $questionSingle->tms;
            $questionExportArray['status']                 = $questionSingle->status;
            $questionExportArray['type']                   = $questionSingle->type;
            $questionExportArray['label']                  = $questionSingle->label;
            $questionExportArray['description']            = $questionSingle->description;
            $questionExportArray['show_photo']             = $questionSingle->show_photo;
            $questionExportArray['authorize_answer_photo'] = $questionSingle->authorize_answer_photo;
            $questionExportArray['enter_comment']          = $questionSingle->enter_comment;
            $questionExportArray['photo_ok']               = $questionSingle->photo_ok;
            $questionExportArray['photo_ko']               = $questionSingle->photo_ko;

            $dolismqExportArray['questions'][$questionSingle->id] = $questionExportArray;

            $allAnswers = $answer->fetchAll();
            if (is_array($allAnswers) && !empty($allAnswers)) {
                foreach ($allAnswers as $answerSingle) {
                    if ($answerSingle->fk_question == $questionSingle->id) {
                        $answerExportArray['rowid']             = $answerSingle->id;
                        $answerExportArray['ref']               = $answerSingle->ref;
                        $answerExportArray['date_creation']     = $answerSingle->date_creation;
                        $answerExportArray['date_modification'] = $answerSingle->tms;
                        $answerExportArray['status']            = $answerSingle->status;
                        $answerExportArray['value']             = $answerSingle->value;
                        $answerExportArray['position']          = $answerSingle->position;
                        $answerExportArray['pictogram']         = $answerSingle->pictogram;
                        $answerExportArray['color']             = $answerSingle->color;
                        $answerExportArray['fk_question']       = $answerSingle->fk_question;

                        $dolismqExportArray['questions'][$questionSingle->id]['answers'][$answerSingle->id] = $answerExportArray;
                    }
                }
            }
        }
    }

    $dolismqExportArray = json_encode($dolismqExportArray, JSON_PRETTY_PRINT);

    $filedir = $upload_dir . '/temp/';
    $export_base = $filedir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_question_answer_export';
    $filename = $export_base . '.json';

    file_put_contents($filename, $dolismqExportArray);

    $zip = new ZipArchive();
    if ($zip->open($export_base . '.zip', ZipArchive::CREATE ) === TRUE) {
        $zip->addFile($filename, basename($filename));
        $zip->close();
        $filenamezip = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_question_answer_export.zip';
        $filepath = DOL_URL_ROOT . '/document.php?modulepart=dolismq&file=' . urlencode('temp/'.$filenamezip);

        ?>
        <script>
            var alink = document.createElement( 'a' );
            alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
            alink.setAttribute('download', <?php echo json_encode($filenamezip); ?>);
            alink.click();
        </script>
        <?php
        $fileExportGlobals = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
    }
}

// Import JSON file
if (GETPOST('dataMigrationImportJson', 'alpha') && $permissionToWrite) {
    if (!empty($_FILES)) {
        if (!preg_match('/question_answer_export.zip/', $_FILES['dataMigrationImportJsonFile']['name'][0]) || $_FILES['dataMigrationImportJsonFile']['size'][0] < 1) {
            setEventMessages($langs->trans('ErrorArchiveNotWellFormattedZIP'), [], 'errors');
        } else {
            if (is_array($_FILES['dataMigrationImportJsonFile']['tmp_name'])) {
                $userFiles = $_FILES['dataMigrationImportJsonFile']['tmp_name'];
            } else {
                $userFiles = array($_FILES['dataMigrationImportJsonFile']['tmp_name']);
            }

            foreach ($userFiles as $key => $userFile) {
                if (empty($_FILES['dataMigrationImportJsonFile']['tmp_name'][$key])) {
                    $error++;
                    if ($_FILES['dataMigrationImportJsonFile']['error'][$key] == 1 || $_FILES['dataMigrationImportJsonFile']['error'][$key] == 2) {
                        setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
                    } else {
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), [], 'errors');
                    }
                }
            }

            $result = 0;
            if (!$error) {
                $filedir = $upload_dir . '/temp/';
                if (!empty($filedir)) {
                    $result = dol_add_file_process($filedir, 1, 1, 'dataMigrationImportJsonFile', '', null, '', 0);
                }
            }

            if ($result > 0) {
                $zip = new ZipArchive;
                if ($zip->open($filedir . $_FILES['dataMigrationImportJsonFile']['name'][0]) === TRUE) {
                    $zip->extractTo($filedir);
                    $zip->close();
                }
            }
            $filename = preg_replace('/\.zip/', '.json', $_FILES['dataMigrationImportJsonFile']['name'][0]);

            $json               = file_get_contents($filedir . $filename);
            $dolismqExportArray = json_decode($json, true);
            $error              = 0;
            $count              = 0;

            if (is_array($dolismqExportArray['sheets']) && !empty($dolismqExportArray['sheets'])) {
                foreach ($dolismqExportArray['sheets'] as $sheetSingle) {
                    $count++;
                    $sheet->ref            = $refSheetMod->getNextValue($sheet);
                    $sheet->date_creation  = $now;
                    $sheet->label          = $sheetSingle['label'];
                    $sheet->description    = $sheetSingle['description'];
                    $sheet->element_linked = $sheetSingle['element_linked'];
                    $sheet->entity         = $conf->entity;

                    $sheetId = $sheet->create($user);

                    if ($sheetId > 0) {
                        $sheet->setStatusCommon($user, $sheetSingle['status'], true);
                        $tmpElementSheetArray[$sheetSingle['rowid']] = $sheet->id;
                    } else {
                        $error++;
                    }
                }
                setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Sheets'), $error, $count));
            }

            $error = 0;
            $count = 0;
            if (is_array($dolismqExportArray['questions']) && !empty($dolismqExportArray['questions'])) {
                foreach ($dolismqExportArray['questions'] as $questionSingle) {
                    $count++;
                    $question->ref                    = $refQuestionMod->getNextValue($question);
                    $question->date_creation          = $now;
                    $question->type                   = $questionSingle['type'];
                    $question->label                  = $questionSingle['label'];
                    $question->description            = $questionSingle['description'];
                    $question->show_photo             = $questionSingle['show_photo'];
                    $question->authorize_answer_photo = $questionSingle['authorize_answer_photo'];
                    $question->enter_comment          = $questionSingle['enter_comment'];
                    $question->photo_ok               = '';
                    $question->photo_ko               = '';

                    $questionId = $question->create($user);

                    if ($questionId > 0) {
                        $question->setStatusCommon($user, $questionSingle['status'], true);

                        if (array_key_exists('element_element', $dolismqExportArray) && !empty($dolismqExportArray['element_element'])) {
                            foreach ($dolismqExportArray['element_element'] as $key => $value) {
                                if (isset($tmpElementSheetArray[$key]) && in_array($questionSingle['rowid'], $dolismqExportArray['element_element'][$key])) {
                                    $question->fetch($questionId);
                                    $question->add_object_linked('dolismq_sheet', $tmpElementSheetArray[$key]);

                                    $sheet->fetch($tmpElementSheetArray[$key]);
                                    $questionsLinked = $sheet->fetchQuestionsLinked($tmpElementSheetArray[$key], 'dolismq_sheet');
                                    $questionIds     = $sheet->linkedObjectsIds['dolismq_question'];

                                    $sheet->updateQuestionsPosition($questionIds);
                                }
                            }
                        }

                        if (array_key_exists('answers', $questionSingle) && !empty($questionSingle['answers'])) {
                            foreach ($questionSingle['answers'] as $answerSingle) {
                                $count++;
                                $answer->ref                     = $refAnswerMod->getNextValue($answer);
                                $answer->date_creation           = $now;
                                $answer->status                  = $answerSingle['status'];
                                $answer->value                   = $answerSingle['value'];
                                $answer->position                = $answerSingle['position'];
                                $answer->pictogram               = $answerSingle['pictogram'];
                                $answer->color                   = $answerSingle['color'];
                                $answer->fk_question             = $questionId;

                                $answerId = $answer->create($user);

                                if ($answerId <= 0) {
                                    $error++;
                                }
                            }
                        }
                    } else {
                        $error++;
                    }
                }
                setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Questions'), $error, $count));
            }
        }
	}
}

/*
 * View
 */

$title    = $langs->trans('Tools', 'DoliSMQ');
$help_url = 'FR:Module_DoliSMQ';

saturne_header(0,'', $title);

print load_fiche_titre($langs->trans('Tools'), '', 'wrench');

print load_fiche_titre($langs->trans("DataMigrationDoliSMQToFile"), '', '');

print '<form class="data-migration-export-global-from" name="dataMigrationExportGlobal" id="dataMigrationExportGlobal" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="dataMigrationExportGlobal">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

// Export sheets, questions and answers data from DoliSMQ
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportSQA');
print "</td><td>";
print $langs->trans('DataMigrationExportSQADescription');
print '</td>';

print '<td class="center data-migration-export-global">';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationExportSQA" value="' . $langs->trans("ExportData") . '">';
print '</td>';
print '</tr>';

// Export questions and answers data from DoliSMQ
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportQA');
print "</td><td>";
print $langs->trans('DataMigrationExportQADescription');
print '</td>';

print '<td class="center data-migration-export-global">';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationExportQA" value="' . $langs->trans("ExportData") . '">';
print '</td>';
print '</tr>';
print '</form>';

print load_fiche_titre($langs->trans("DataMigrationFileToDolibarr"), '', '');

print '<form class="data-migration" name="DataMigration" id="DataMigration" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportZIP');
print "</td><td>";
print $langs->trans('DataMigrationImportZIPDescription');
print '</td>';

print '<td class="center data-migration-import-json">';
print '<input class="flat" type="file" name="dataMigrationImportJsonFile[]" id="data-migration-import-json" />';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportJson" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '</form>';

// Page end
llxFooter();
$db->close();
