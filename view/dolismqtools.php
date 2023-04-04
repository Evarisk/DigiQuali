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

require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../lib/dolismq.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

saturne_load_langs();

$error          = 0;
$question       = new Question($db);
$sheet          = new Sheet($db);
$upload_dir     = $conf->dolismq->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Security check
$permissiontoread = $user->rights->dolismq->read;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Import text file
if (GETPOST('dataMigrationImportTxt', 'alpha')) {
	if (!empty($_FILES)) {
		if (!preg_match('/.txt/', $_FILES['dataMigrationImportTxtFile']['name']) || $_FILES['dataMigrationImportTxtFile']['size'] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedTXT'), null, 'errors');
		} else {
			$userfiles = $_FILES['dataMigrationImportTxtFile']['tmp_name'];
			if (empty($_FILES['dataMigrationImportTxtFile']['tmp_name'])) {
				$error++;
				if ($_FILES['dataMigrationImportTxtFile']['error'] == 1 || $_FILES['dataMigrationImportTxtFile']['error'] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}

			if (!$error) {
				$filedir = $upload_dir . '/temp/';
				if (!empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportTxtFile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$fileName = $_FILES['dataMigrationImportTxtFile']['name'];
				$fileContent = file_get_contents($filedir . $fileName);
				$fileContent = explode('%point%', $fileContent);

				$fileInfo = $fileContent[0];
				$fileInfo = str_replace('%task%', '', $fileInfo);

				$sheet->date_creation = dol_now('tzuser');
				$sheet->label         = $fileInfo;
				$result               = $sheet->create($user);

				foreach ($fileContent as $key => $value) {
					if ($key < 1) continue;
					if ($value[1] == '[') {
						$startPos = strpos($value, '[') + 1;
						$endPos   = strpos($value, ']');

						$questionLabel = substr($value, $startPos, $endPos + $startPos - 4);
						$questionDesc  = substr($value, $endPos + 1, dol_strlen($value));
					} else {
						$questionLabel = '';
						$questionDesc = $value;
					}

					$question->date_creation = dol_now('tzuser');
					$question->label         = $questionLabel;
					$question->description   = $questionDesc;
					$question->status        = $question::STATUS_LOCKED;

					$result                  = $question->create($user);

					if ($result > 0) {
						$question->add_object_linked('dolismq_sheet', $sheet->id);
						$questionsLinked = $sheet->fetchQuestionsLinked($sheet->id, 'dolismq_sheet');
						$questionIds     = $sheet->linkedObjectsIds['dolismq_question'];
						$sheet->updateQuestionsPosition($questionIds);
					}
				}
			}
		}
	}
}

// Import JSON file
if (GETPOST('dataMigrationImportJson', 'alpha')) {
	if (!empty($_FILES)) {
		if (!preg_match('/.json/', $_FILES['dataMigrationImportJsonFile']['name']) || $_FILES['dataMigrationImportJsonFile']['size'] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedJSON'), null, 'errors');
		} else {
			$userfiles = $_FILES['dataMigrationImportJsonFile']['tmp_name'];
			if (empty($_FILES['dataMigrationImportJsonFile']['tmp_name'])) {
				$error++;
				if ($_FILES['dataMigrationImportJsonFile']['error'] == 1 || $_FILES['dataMigrationImportJsonFile']['error'] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}

			if (!$error) {
				$filedir = $upload_dir . '/temp/';
				if (!empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportJsonFile', '', null, '', 0, null);
				}
			}
		}
	}
}

// Import CSV file
if (GETPOST('dataMigrationImportCsv', 'alpha')) {
	if (!empty($_FILES)) {
		if (!preg_match('/.csv/', $_FILES['dataMigrationImportCsvFile']['name']) || $_FILES['dataMigrationImportCsvFile']['size'] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedCSV'), null, 'errors');
		} else {
			$userfiles = $_FILES['dataMigrationImportCsvFile']['tmp_name'];
			if (empty($_FILES['dataMigrationImportCsvFile']['tmp_name'])) {
				$error++;
				if ($_FILES['dataMigrationImportCsvFile']['error'] == 1 || $_FILES['dataMigrationImportCsvFile']['error'] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}

			if (!$error) {
				$filedir = $upload_dir . '/temp/';
				if (!empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportCsvFile', '', null, '', 0, null);
				}
			}
		}
	}
}

if (GETPOST('dataMigrationImportZip', 'alpha')) {
	if (!empty($_FILES)) {
		if (!preg_match('/.zip/', $_FILES['dataMigrationImportZipFile']['name'][0]) || $_FILES['dataMigrationImportZipFile']['size'][0] < 1) {
			setEventMessages($langs->transnoentitiesnoconv('ErrorArchiveNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportZipFile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportZipFile']['tmp_name'];
			else $userfiles = array($_FILES['dataMigrationImportZipFile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportZipFile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportZipFile']['error'][$key] == 1 || $_FILES['dataMigrationImportZipFile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if (!$error) {
				$filedir = $upload_dir . '/temp/';
				if (!empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportZipFile', '', null, '', 0, null);
				}
			}

			$filename = $_FILES['dataMigrationImportZipFile']['name'][0];

			$dirName = $_FILES['dataMigrationImportZipFile']['name'][0];
			$dirName = str_replace('.zip', '/', $dirName);

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportZipFile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir . $dirName);
					$zip->close();
				}
			}

			/*$json                = file_get_contents($filedir . $filename);
			if ($json > 0) {
				$dolismqExportArray = json_decode($json, true);
				$dolismqExportArray = end($dolismqExportArray);
			}

			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($dolismqExportArray['digiriskelements']['digiriskelements']));
			foreach ($it as $key => $v) {
				$element[$key][] = $v;
			}*/
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

print load_fiche_titre($langs->trans("DataMigrationFileToDolibarr"), '', '');

print '<span class="opacitymedium">'.$langs->trans("RequiredFormatStyle") . ' : </span>' . '<u> <a class="wordbreak" href="https://github.com/Eoxia/checklist/blob/master/README.md">' . $langs->trans('ConfigFormatGithub') . '</a> </u> </br> </br>';

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
print $langs->trans('DataMigrationImportTXT');
print "</td><td>";
print $langs->trans('DataMigrationImportTXTDescription');
print '</td>';

print '<td class="center data-migration-import-txt">';
print '<input class="flat" type="file" name="dataMigrationImportTxtFile" id="data-migration-import-txt" />';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportTxt" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportJSON');
print "</td><td>";
print $langs->trans('DataMigrationImportJSONDescription');
print '</td>';

print '<td class="center data-migration-import-json">';
print '<input class="flat" type="file" name="dataMigrationImportJsonFile" id="data-migration-import-json" />';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportJson" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportCSV');
print "</td><td>";
print $langs->trans('DataMigrationImportCSVDescription');
print '</td>';

print '<td class="center data-migration-import-csv">';
print '<input class="flat" type="file" name="dataMigrationImportCsvFile" id="data-migration-import-csv" />';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportCsv" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportZIP');
print "</td><td>";
print $langs->trans('DataMigrationImportZIPDescription');
print '</td>';

print '<td class="center data-migration-import-zip">';
print '<input class="flat" type="file" name="dataMigrationImportZipFile[]" id="data-migration-import-zip" />';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportZip" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

// Page end
llxFooter();
$db->close();
