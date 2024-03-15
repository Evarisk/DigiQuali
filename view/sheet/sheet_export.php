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
 *   	\file       view/sheet/sheet_card.php
 *		\ingroup    digiquali
 *		\brief      Page to create/edit/view sheet
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["other", "product", 'bills', 'orders']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
// Technical objets
$object   = new Sheet($db);
$question = new Question($db);
$answer   = new Answer($db);

// View objects
$form = new Form($db);
$now  = dol_now();

$upload_dir = $conf->digiquali->multidir_output[isset($conf->entity) ? $conf->entity : 1];

$hookmanager->initHooks(array('sheetexport')); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissionToRead   = $user->rights->digiquali->sheet->read;
$permissionToAdd    = $user->rights->digiquali->sheet->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissionToDelete = $user->rights->digiquali->sheet->delete || ($permissionToAdd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

// Security check - Protection if external user
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiquali/view/sheet/sheet_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiquali/view/sheet/sheet_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'export_sheet_data' && $permissionToRead) {
        $exportName = (!empty($object->label) ? dol_string_nospecial($object->label, '_', '', ['-']) : $object->ref);

		$digiqualiExportArray = [];
		$sheetExportArray['rowid']               = $object->id;
		$sheetExportArray['ref']                 = $object->ref;
		$sheetExportArray['status']              = $object->status;
		$sheetExportArray['label']               = $object->label;
		$sheetExportArray['description']         = $object->description;
		$sheetExportArray['element_linked']      = $object->element_linked;
		$sheetExportArray['mandatory_questions'] = $object->mandatory_questions;

		$digiqualiExportArray['sheets'][$object->id] = $sheetExportArray;

        $object->fetchObjectLinked($object->id, 'digiquali_' . $object->element);
		$questionsLinked = $object->linkedObjects['digiquali_question'];

		if (is_array($questionsLinked) && !empty($questionsLinked)) {
			foreach ($questionsLinked as $questionSingle) {
				$digiqualiExportArray['element_element'][$object->id][] = $questionSingle->id;
				$questionExportArray['rowid']                  = $questionSingle->id;
				$questionExportArray['ref']                    = $questionSingle->ref;
				$questionExportArray['status']                 = $questionSingle->status;
				$questionExportArray['type']                   = $questionSingle->type;
				$questionExportArray['label']                  = $questionSingle->label;
				$questionExportArray['description']            = $questionSingle->description;
				$questionExportArray['show_photo']             = $questionSingle->show_photo;
				$questionExportArray['authorize_answer_photo'] = $questionSingle->authorize_answer_photo;
				$questionExportArray['enter_comment']          = $questionSingle->enter_comment;

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

		$digiqualiExportJSON = json_encode($digiqualiExportArray, JSON_PRETTY_PRINT);

		$fileDir    = $upload_dir . '/temp/';
		$exportBase = $fileDir . dol_print_date($now, 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export';
		$fileName   = $exportBase . '.json';

		file_put_contents($fileName, $digiqualiExportJSON);

		$zip = new ZipArchive();
		if ($zip->open($exportBase . '.zip', ZipArchive::CREATE ) === TRUE) {
			setEventMessage($langs->transnoentities("ExportWellDone"));
			$zip->addFile($fileName, basename($fileName));
			$zip->close();
			$fileNameZip = dol_print_date($now, 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export.zip';
			$filepath = DOL_URL_ROOT . '/document.php?modulepart=digiquali&file=' . urlencode('temp/'.$fileNameZip);
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
}

/*
 * View
 */

$title    = $langs->trans('Tools', 'DigiQuali');
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title);

saturne_get_fiche_head($object, 'export', $title);
saturne_banner_tab($object);

print load_fiche_titre($langs->trans("ExportSheetData"), '', '');

print '<form class="sheet-data-export" name="export_sheet_data" id="export_sheet_data" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="export_sheet_data">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ExportSheetData');
print "</td><td>";
print $langs->trans('ExportSheetDataDescription');
print '</td>';

print '<td class="center data-migration-export-global">';
print '<input type="submit" class="button reposition data-migration-submit" name="data_migration_export_sqa" value="' . $langs->trans("ExportData") . '">';
print '</td>';
print '</tr>';

print '</tr>';
print '</table>';
print '</form>';

print $langs->trans('ToImportDataGoToImportPage', '../digiqualitools.php', $langs->trans('ClickHere'));

// End of page
llxFooter();
$db->close();
