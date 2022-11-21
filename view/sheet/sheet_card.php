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
 *   	\file       view/sheet/sheet_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view sheet
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/dolismq_sheet.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/sheet/mod_sheet_standard.php';
require_once '../../lib/dolismq_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other", "product"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'sheetcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
// Technical objets
$object      = new Sheet($db);
$question    = new Question($db);
$extrafields = new ExtraFields($db);
$refSheetMod = new $conf->global->DOLISMQ_SHEET_ADDON($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('sheetcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread   = $user->rights->dolismq->sheet->read;
$permissiontoadd    = $user->rights->dolismq->sheet->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->sheet->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

// Security check - Protection if external user
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolismq/view/sheet/sheet_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/sheet/sheet_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'addQuestion' && $permissiontoadd) {
		$questionId = GETPOST('questionId');
		if ($questionId > 0) {
			$question->fetch($questionId);
			$question->add_object_linked('dolismq_' . $object->element,$id);
			setEventMessages($langs->trans('addQuestionLink') . ' ' . $question->ref, array());

			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
			exit;
		} else {
			setEventMessages($langs->trans('ErrorNoQuestionSelected'), null, 'errors');
		}

	}

	if ($action == 'unlinkQuestion' && $permissiontoadd) {
		$questionId = GETPOST('questionId');
		$question->fetch($questionId);
		$question->element = 'dolismq_'.$question->element;
		$question->deleteObjectLinked($id, 'dolismq_' . $object->element);
		setEventMessages($langs->trans('removeQuestionLink') . ' ' . $question->ref, array());

		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

	if ($action == 'add' && $permissiontoadd) {
		if (is_array(GETPOST('linked_object')) && !empty(GETPOST('linked_object'))) {
			foreach (GETPOST('linked_object') as $linked_object_type) {
				$showArray[$linked_object_type] = 1;
			}
		}

		$object->element_linked = json_encode($showArray);
	}

	if ($action == 'update' && $permissiontoadd) {
		if (is_array(GETPOST('linked_object')) && !empty(GETPOST('linked_object'))) {
			foreach (GETPOST('linked_object') as $linked_object_type) {
				$showArray[$linked_object_type] = 1;
			}
		}

		$object->element_linked = json_encode($showArray);

		$categories = GETPOST('categories', 'array');
		$object->setCategories(GETPOST('categories', 'array'));
	}

	if ($action == 'moveLine' && $permissiontoadd) {
		$idsArray = json_decode(file_get_contents('php://input'), true);
		$object->updateQuestionsPosition($idsArray['order']);
	}

	// Action to delete
	if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
		if (!($object->id > 0)) {
			dol_print_error('', 'Error, object must be fetched before being deleted');
			exit;
		}
		$categories = $object->getCategoriesCommon('sheet');

		if (is_array($categories) && !empty($categories)) {
			foreach ($categories as $cat_id) {

				$category = new Categorie($db);
				$category->fetch($cat_id);
				$category->del_type($object, 'sheet');
			}
		}

		$object->fetchObjectLinked($id, 'dolismq_' . $object->element);
		$object->element = 'dolismq_' . $object->element;

		if (is_array($object->linkedObjects) && !empty($object->linkedObjects)) {
			foreach($object->linkedObjects as $linkedObjectType => $linkedObjectArray) {
				foreach($linkedObjectArray as $linkedObject) {

					if (method_exists($object, 'is_erasable') && $object->is_erasable() > 0) {
						$object->deleteObjectLinked('','',$linkedObject->id, $linkedObjectType);
					}
				}
			}
		}
		exit;
		$result = $object->delete($user);

		if ($result > 0) {
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');

			header("Location: ".$backurlforlist);
			exit;
		} else {
			$error++;
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
		}
		$action = '';
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_setLocked' && $permissiontoadd) {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		if ($object->id > 0) {
			$result = $object->createFromClone($user, $object->id);
			if ($result > 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}
}

/*
 * View
 */

$title         = $langs->trans("Sheet");
$title_create  = $langs->trans("NewSheet");
$title_edit    = $langs->trans("ModifySheet");

$help_url = '';
$morejs   = array("/dolismq/js/dolismq.js");
$morecss  = array("/dolismq/css/dolismq.css");

$elementArray = array(
	'product' => array(
		'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT,
		'langs' => 'ProductOrService',
		'picto' => 'product'
	),
	'productlot' => array(
		'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT,
		'langs' => 'Batch',
		'picto' => 'lot'
	),
	'thirdparty' => array(
		'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY,
		'langs' => 'ThirdParty',
		'picto' => 'building'
	),
	'project' => array(
		'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PROJECT,
		'langs' => 'Project',
		'picto' => 'project'
	),
	'task' => array(
		'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_TASK,
		'langs' => 'Task',
		'picto' => 'projecttask'
	),
);

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate sheet-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="' . $refSheetMod->getNextValue($object) . '">';
	print $refSheetMod->getNextValue($object);
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . GETPOST('label') . '">';
	print '</td></tr>';

	//FK Element
	if (empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT) && empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT) && empty($conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY) && empty($conf->global->DOLISMQ_CONTROL_SHOW_PROJECT) && empty($conf->global->DOLISMQ_CONTROL_SHOW_TASK)) {
		print '<div class="wpeo-notice notice-info">';
		print '<div class="notice-content">';
		print '<div class="notice-subtitle">'.$langs->trans("ConfigElementLinked") . '<a href="' .dol_buildpath('/custom/dolismq/admin/control.php', 2).'">' . ' : ' . $langs->trans('ConfigControl') . '</a>';
		print '</div>';
		print '</div>';
		print '</div>';
	}

	foreach ($elementArray as $key => $element) {
		if (!empty($element['conf'])) {
			print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
			if ($conf->global->DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT) {
				print '<input type="radio" id="show_' . $key . '" name="linked_object[]" value="'.$key.'"">';
			} else {
				print '<input type="checkbox" id="show_' . $key . '" name="linked_object[]" value="'.$key.'">';
			}
			print '</td></tr>';
		}
	}

	if (!empty($conf->categorie->enabled)) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('sheet', '', 'parent', 64, 0, 1);
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($title_edit, '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="conf_unique_linked_element" value="'.$conf->global->DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT.'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit sheet-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . $object->label . '">';
	print '</td></tr>';

	//FK Element
	$elementLinked = json_decode($object->element_linked);

	foreach ($elementArray as $key => $element) {
		if (!empty($element['conf'])) {
			print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
			if ($conf->global->DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT) {
				print '<input type="radio" id="show_' . $key . '" name="linked_object[]" value="'.$key.'"'.(($elementLinked->$key > 0) ? 'checked=checked' : '').'>';
			} else {
				print '<input type="checkbox" id="show_' . $key . '" name="linked_object[]" value="'.$key.'"'.(($elementLinked->$key > 0) ? 'checked=checked' : '').'>';
			}
			print '</td></tr>';
		}
	}

	// Tags-Categories
	if ($conf->categorie->enabled) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('sheet', '', 'parent', 64, 0, 1);
		$c = new Categorie($db);
		$cats = $c->containing($object->id, 'sheet');
		$arrayselected = array();
		if (is_array($cats)) {
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
		}
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = sheetPrepareHead($object);
	print dol_get_fiche_head($head, 'sheetCard', $langs->trans("Sheet"), -1, 'object_'.$object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteSheet'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockSheet'), $langs->trans('ConfirmLockSheet', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js

		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneSheet', $object->ref), 'confirm_clone', '', 'yes', 'actionButtonClone', 350, 600);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/dolismq/view/sheet/sheet_list.php', 1).'?restore_lastsearch_values=1'.'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'sheet', 1);
		print "</td></tr>";
	}

	$elementLinked = json_decode($object->element_linked);

	//FK Element
	$elementArray = array(
		'product' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT,
			'langs' => 'ProductOrService',
			'picto' => 'product'
		),
		'productlot' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT,
			'langs' => 'Batch',
			'picto' => 'lot'
		),
		'thirdparty' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY,
			'langs' => 'ThirdParty',
			'picto' => 'building'
		),
		'project' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PROJECT,
			'langs' => 'Project',
			'picto' => 'project'
		),
		'task' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_TASK,
			'langs' => 'Task',
			'picto' => 'projecttask'
		),
	);

	foreach ($elementArray as $key => $element) {
		if ($elementLinked->$key > 0) {
			if (!empty($element['conf'])) {
				print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
				print '<input type="radio" id="show_' . $key . '" name="show_' . $key . '" checked disabled>';
				print '</td></tr>';
			}
		}
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	$object->fetchObjectLinked($id, 'dolismq_' . $object->element);
	$questionIds = $object->linkedObjectsIds['dolismq_question'];
	if (is_array($questionIds) && !empty($questionIds)) {
		ksort($questionIds);
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {

			// Back to draft
			print '<span class="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? 'actionButtonLock' : '') . '" title="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? '' : dol_escape_htmltag($langs->trans("AllQuestionsMustHaveLocked"))) . '">' . $langs->trans("Lock") . '</span>';
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit', '', $permissiontoadd);
			}

			print '<span class="butAction" id="actionButtonClone" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone' . '">' . $langs->trans("ToClone") . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			}
		}
		print '</div>'."\n";
	}

	// QUESTIONS LINES
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans("LinkedQuestionsList"), '', '');
	print '<table id="tablelines" class="centpercent noborder noshadow">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

	// Define colspan for the button 'Add'
	$colspan = 3;
	?>
	<script>
		$(document).ready(function(){
			$(".move-line").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
			$(".move-line").css("background-repeat","no-repeat");
			$(".move-line").css("background-position","center center");
			$('#tablelines tbody').sortable({
				handle: '.move-line',
				connectWith:'#tablelines tbody .line-row',
				tolerance:'intersect',
				over:function(event,ui){
				},
				stop: function(event, ui) {
					let token = $('.fiche').find('input[name="token"]').val();

					let separator = '&'
					if (document.URL.match(/action=/)) {
						document.URL = document.URL.split(/\?/)[0]
						separator = '?'
					}
					lineOrder = [];
					$('.line-row').each(function(  ) {
						lineOrder.push($(this).attr('id'));
					});
					console.log(lineOrder)
					$.ajax({
						url: document.URL + separator + "action=moveLine&token=" + token,
						type: "POST",
						data: JSON.stringify({
							order: lineOrder
						}),
						processData: false,
						contentType: false,
						success: function ( resp ) {
						}
					});
				}
			});

		});
	</script>
	<?php
	// Lines
	print '<thead><tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('Label') . '</td>';
	print '<td>' . $langs->trans('Description') . '</td>';
	print '<td>' . $langs->trans('PhotoOk') . '</td>';
	print '<td>' . $langs->trans('PhotoKo') . '</td>';
	print '<td>' . $langs->trans('Status') . '</td>';
	print '<td class="center">' . $langs->trans('Action') . '</td>';
	print '<td class="center"></td>';
	print '</tr></thead>';

	if ( ! empty($questionIds) && $questionIds > 0) {
		print '<tbody><tr>';
		foreach ($questionIds as $questionId) {
			$item = $question;
			$item->fetch($questionId);

			print '<tr id="'. $item->id .'" class="line-row oddeven">';
			print '<td>';
			print $item->getNomUrl();
			print '</td>';

			print '<td>';
			print $item->label;
			print '</td>';

			print '<td>';
			print $item->description;
			print '</td>';

			print '<td>';
			if (dol_strlen($item->photo_ok)) {
				$urladvanced               = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ok/' . $item->photo_ok, 0, 'entity=' . $conf->entity);
				if ($urladvanced) print '<a href="' . $urladvanced . '">';
				print '<img width="40" class="photo photo-ok clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ok/thumbs/' . preg_replace('/\./', '_mini.', $item->photo_ok)) . '" >';
				print '</a>';
			} else {
				print '<img height="40" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
			}
			print '</td>';
			print '<td>';
			if (dol_strlen($item->photo_ko)) {
				$urladvanced               = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ko/' . $item->photo_ko, 0, 'entity=' . $conf->entity);
				if ($urladvanced) print '<a href="' . $urladvanced . '">';
				print '<img width="40" class="photo photo-ko clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ko/thumbs/' . preg_replace('/\./', '_mini.', $item->photo_ko)) . '" >';
				print '</a>';
			} else {
				print '<img height="40" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
			}
			print '</td>';

			print '<td>';
			print $item->getLibStatut(5);
			print '</td>';


			print '<td class="center">';
			if ($object->status != 2) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=unlinkQuestion&questionId=' . $item->id . '">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			print '<td class="move-line ui-sortable-handle">';
			print '</td>';
			print '</tr>';
			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
		}
		print '</tr></tbody>';
	}

	if ($object->status != 2) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addQuestion">';
		print '<input type="hidden" name="id" value="' . $id . '">';

		print '<tr class="add-line"><td class="">';
		print $question->select_question_list(0, 'questionId', '', '1', 0, 0, array(), '', 0, 0, 'disabled', '', false, $questionIds);
		print '</td>';
		print '<td>';
		print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Add") . '">';
		print '</td></tr>';

		print '</form>';
	}

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', dol_buildpath('/dolismq/view/sheet/sheet_agenda.php', 1).'?id='.$object->id);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
