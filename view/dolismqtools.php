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
} elseif (file_exists('../../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../../dolismq.main.inc.php';
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

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize objects
// Technical objets
$answer   = new Answer($db);
$question = new Question($db);
$sheet    = new Sheet($db);

$error      = 0;
$now        = dol_now();
$upload_dir = $conf->dolismq->multidir_output[isset($conf->entity) ? $conf->entity : 1];

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

if ($action == 'data_migration_export_global' && $permissionToRead) {
	$dolismqExportArray = [];
	if (GETPOST('data_migration_export_sqa', 'alpha')) {
		$allSheets  = $sheet->fetchAll();
		$exportName = 'sheet_question_answer';
		if (is_array($allSheets) && !empty($allSheets)) {
			foreach ($allSheets as $sheetSingle) {
				$sheetExportArray['rowid']               = $sheetSingle->id;
				$sheetExportArray['ref']                 = $sheetSingle->ref;
				$sheetExportArray['status']              = $sheetSingle->status;
				$sheetExportArray['label']               = $sheetSingle->label;
				$sheetExportArray['description']         = $sheetSingle->description;
				$sheetExportArray['element_linked']      = $sheetSingle->element_linked;
				$sheetExportArray['mandatory_questions'] = $sheetSingle->mandatory_questions;

				$dolismqExportArray['sheets'][$sheetSingle->id] = $sheetExportArray;

				$sheetSingle->fetchQuestionsLinked($sheetSingle->id, 'dolismq_sheet', null, '', 'OR', 1, 'sourcetype', 0);
				$questionsLinked = $sheetSingle->linkedObjectsIds['dolismq_question'];
				if (is_array($questionsLinked) && !empty($questionsLinked)) {
					ksort($questionsLinked);
					foreach ($questionsLinked as $questionId) {
						$dolismqExportArray['element_element'][$sheetSingle->id][] = $questionId;
					}
				}
			}
		}
	} else {
		$exportName = 'question_answer';
	}

    $allQuestions = $question->fetchAll();
    if (is_array($allQuestions) && !empty($allQuestions)) {
        foreach ($allQuestions as $questionSingle) {
            $questionExportArray['rowid']                  = $questionSingle->id;
            $questionExportArray['ref']                    = $questionSingle->ref;
            $questionExportArray['status']                 = $questionSingle->status;
            $questionExportArray['type']                   = $questionSingle->type;
            $questionExportArray['label']                  = $questionSingle->label;
            $questionExportArray['description']            = $questionSingle->description;
            $questionExportArray['show_photo']             = $questionSingle->show_photo;
            $questionExportArray['authorize_answer_photo'] = $questionSingle->authorize_answer_photo;
            $questionExportArray['enter_comment']          = $questionSingle->enter_comment;

            $dolismqExportArray['questions'][$questionSingle->id] = $questionExportArray;
		}
	}

	$allAnswers = $answer->fetchAll();
	if (is_array($allAnswers) && !empty($allAnswers)) {
		foreach ($allAnswers as $answerSingle) {
			$answerExportArray['rowid']       = $answerSingle->id;
			$answerExportArray['ref']         = $answerSingle->ref;
			$answerExportArray['status']      = $answerSingle->status;
			$answerExportArray['value']       = $answerSingle->value;
			$answerExportArray['position']    = $answerSingle->position;
			$answerExportArray['pictogram']   = $answerSingle->pictogram;
			$answerExportArray['color']       = $answerSingle->color;
			$answerExportArray['fk_question'] = $answerSingle->fk_question;

			$dolismqExportArray['questions'][$answerSingle->fk_question]['answers'][$answerSingle->id] = $answerExportArray;
		}
    }

    $dolismqExportJSON = json_encode($dolismqExportArray, JSON_PRETTY_PRINT);

    $fileDir    = $upload_dir . '/temp/';
    $exportBase = $fileDir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export';
    $fileName   = $exportBase . '.json';

    file_put_contents($fileName, $dolismqExportJSON);

    $zip = new ZipArchive();
    if ($zip->open($exportBase . '.zip', ZipArchive::CREATE ) === TRUE) {
        $zip->addFile($fileName, basename($fileName));
        $zip->close();
        $fileNameZip = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export.zip';
        $filepath = DOL_URL_ROOT . '/document.php?modulepart=dolismq&file=' . urlencode('temp/'.$fileNameZip);

        ?>
        <script>
            var alink = document.createElement( 'a' );
            alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
            alink.setAttribute('download', <?php echo json_encode($fileNameZip); ?>);
            alink.click();
        </script>
        <?php
        $fileExportGlobals = dol_dir_list($fileDir, "files", 0, '', '', '', '', 1);
    }
}

// Import ZIP file
if (GETPOST('dataMigrationImportZip', 'alpha') && $permissionToWrite) {
    if (!empty($_FILES)) {
        if (!preg_match('/question_answer_export.zip/', $_FILES['dataMigrationImportZipFile']['name'][0]) || $_FILES['dataMigrationImportZipFile']['size'][0] < 1) {
            setEventMessages($langs->trans('ErrorArchiveNotWellFormattedZIP'), [], 'errors');
        } else {
            if (is_array($_FILES['dataMigrationImportZipFile']['tmp_name'])) {
                $userFiles = $_FILES['dataMigrationImportZipFile']['tmp_name'];
            } else {
                $userFiles = array($_FILES['dataMigrationImportZipFile']['tmp_name']);
            }

            foreach ($userFiles as $key => $userFile) {
                if (empty($_FILES['dataMigrationImportZipFile']['tmp_name'][$key])) {
                    $error++;
                    if ($_FILES['dataMigrationImportZipFile']['error'][$key] == 1 || $_FILES['dataMigrationImportZipFile']['error'][$key] == 2) {
                        setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
                    } else {
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), [], 'errors');
                    }
                }
            }

            $result = 0;
            if (!$error) {
                $fileDir = $upload_dir . '/temp/';
                if (!empty($fileDir)) {
                    $result = dol_add_file_process($fileDir, 1, 1, 'dataMigrationImportZipFile', '', null, '', 0);
                }
            }

            if ($result > 0) {
                $zip = new ZipArchive;
                if ($zip->open($fileDir . $_FILES['dataMigrationImportZipFile']['name'][0]) === TRUE) {
                    $zip->extractTo($fileDir);
                    $zip->close();
                }
            }
            $fileName = preg_replace('/\.zip/', '.json', $_FILES['dataMigrationImportZipFile']['name'][0]);

            $json               = file_get_contents($fileDir . $fileName);
            $dolismqExportArray = json_decode($json, true);
            $error              = 0;

            if (is_array($dolismqExportArray['sheets']) && !empty($dolismqExportArray['sheets'])) {
                foreach ($dolismqExportArray['sheets'] as $sheetSingle) {
                    $sheet->label               = $sheetSingle['label'];
                    $sheet->description         = $sheetSingle['description'];
					$sheet->element_linked      = $sheetSingle['element_linked'];
					$sheet->mandatory_questions = $sheetSingle['mandatory_questions'];
					$sheet->status              = $sheetSingle['status'];

                    $sheetId = $sheet->create($user);

                    if ($sheetId > 0) {
                        $tmpElementSheetArray[$sheetSingle['rowid']] = $sheet->id;
                    } else {
                        $error++;
                    }
                }
				$sheetCount = count($dolismqExportArray['sheets']);
				setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Sheets'), $error, $sheetCount));
            }

            $error = 0;
            if (is_array($dolismqExportArray['questions']) && !empty($dolismqExportArray['questions'])) {
				foreach ($dolismqExportArray['questions'] as $questionSingle) {
                    $question->type                   = $questionSingle['type'];
                    $question->label                  = $questionSingle['label'];
                    $question->description            = $questionSingle['description'];
                    $question->show_photo             = $questionSingle['show_photo'];
                    $question->authorize_answer_photo = $questionSingle['authorize_answer_photo'];
                    $question->enter_comment          = $questionSingle['enter_comment'];
					$question->status                 = $questionSingle['status'];

                    $questionId = $question->create($user);

                    if ($questionId > 0) {
                        if (array_key_exists('element_element', $dolismqExportArray) && !empty($dolismqExportArray['element_element'])) {
                            foreach ($dolismqExportArray['element_element'] as $key => $value) {
                                if (isset($tmpElementSheetArray[$key]) && in_array($questionSingle['rowid'], $dolismqExportArray['element_element'][$key])) {
                                    $question->fetch($questionId);
                                    $question->add_object_linked('dolismq_sheet', $tmpElementSheetArray[$key]);

                                    $sheet->fetch($tmpElementSheetArray[$key]);
                                    $questionsLinked = $sheet->fetchQuestionsLinked($tmpElementSheetArray[$key], 'dolismq_sheet', null, '', 'OR', 1, 'sourcetype', 0);
                                    $questionIds     = $sheet->linkedObjectsIds['dolismq_question'];

                                    $sheet->updateQuestionsPosition($questionIds);
                                }
                            }
                        }

                        if (array_key_exists('answers', $questionSingle) && !empty($questionSingle['answers'])) {
							foreach ($questionSingle['answers'] as $answerSingle) {
                                $answer->status      = $answerSingle['status'];
                                $answer->value       = $answerSingle['value'];
                                $answer->position    = $answerSingle['position'];
                                $answer->pictogram   = $answerSingle['pictogram'];
                                $answer->color       = $answerSingle['color'];
                                $answer->fk_question = $questionId;

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
				$questionCount = count($dolismqExportArray['questions']);
				setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Questions'), $error, $questionCount));
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

print '<form class="data-migration-export-global-from" name="data_migration_export_global" id="data_migration_export_global" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="data_migration_export_global">';

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
print '<input type="submit" class="button reposition data-migration-submit" name="data_migration_export_sqa" value="' . $langs->trans("ExportData") . '">';
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

print '<td class="center">';
print '<input class="flat" type="file" name="dataMigrationImportZipFile[]"/>';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportZip" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '</form>';

// Page end
llxFooter();
$db->close();
