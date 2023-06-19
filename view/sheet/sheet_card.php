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
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view sheet
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
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/dolismq_sheet.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/sheet/mod_sheet_standard.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["other", "product", 'bills', 'orders']);

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
$category    = new Categorie($db);
$refSheetMod = new $conf->global->DOLISMQ_SHEET_ADDON($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('sheetcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST("search_all", 'alpha');
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
saturne_check_access($permissiontoread, $object);

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
			$test = $question->add_object_linked('dolismq_' . $object->element,$id);

			$questionsLinked = 	$object->fetchQuestionsLinked($id, 'dolismq_' . $object->element);
			$questionIds     = $object->linkedObjectsIds['dolismq_question'];
			$object->updateQuestionsPosition($questionIds);

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

		$questionsLinked = 	$object->fetchQuestionsLinked($id, 'dolismq_' . $object->element);
		$questionIds     = $object->linkedObjectsIds['dolismq_question'];
		$object->updateQuestionsPosition($questionIds);

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

		if (empty(GETPOST('categories', 'array'))) {
			$category->fetch($conf->global->DOLISMQ_SHEET_DEFAULT_TAG);
			$defaultCategory[] = $category->id;
			$_POST['categories'] = $defaultCategory;
		}
	}

	if ($action == 'update' && $permissiontoadd) {
		if (is_array(GETPOST('linked_object')) && !empty(GETPOST('linked_object'))) {
			foreach (GETPOST('linked_object') as $linked_object_type) {
				$showArray[$linked_object_type] = 1;
			}
		}
		$object->element_linked = json_encode($showArray);

		if (empty(GETPOST('categories', 'array'))) {
			$category->fetch($conf->global->DOLISMQ_SHEET_DEFAULT_TAG);
			$defaultCategory[] = $category->id;
			$_POST['categories'] = $defaultCategory;
		} else {
			$object->setCategories(GETPOST('categories', 'array'));
		}
	}

	if ($action == 'moveLine' && $permissiontoadd) {
		$idsArray = json_decode(file_get_contents('php://input'), true);
		if (is_array($idsArray['order']) && !empty($idsArray['order'])) {
			$ids = array_values($idsArray['order']);
			$reIndexedIds = array_combine(range(1, count($ids)), array_values($ids));
		}
		$object->updateQuestionsPosition($reIndexedIds);
	}

	// Action to delete
	if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
		if (!($object->id > 0)) {
			dol_print_error('', 'Error, object must be fetched before being deleted');
			exit;
		}

		if (method_exists($object, 'isErasable') && $object->isErasable() <= 0) {
			$langs->load("errors");
			$object->errors = $langs->trans('ErrorQuestionUsedInSheet',$object->ref);
			$result = 0;
		} else {
			$result = $object->delete($user);
		}

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

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_lock' && $permissiontoadd) {
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

    // Action to set status STATUS_ARCHIVED.
    if ($action == 'confirm_archive' && $permissiontoadd) {
        $object->fetch($id);
        if (!$error) {
            $result = $object->setArchived($user);
            if ($result > 0) {
                // Set Archived OK.
                $urltogo = str_replace('__ID__', $result, $backtopage);
                $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
                header('Location: ' . $urltogo);
                exit;
            } elseif (!empty($object->errors)) { // Set Archived KO.
                setEventMessages('', $object->errors, 'errors');
            } else {
                setEventMessages($object->error, [], 'errors');
            }
        }
    }

    if ($action == 'set_mandatory' && $permissiontoadd) {
        $questionId = GETPOST('questionId', 'int');
		$questionRef = GETPOST('questionRef', 'alpha');

        if ($questionId > 0) {
            $mandatoryArray = dol_strlen($object->mandatory_questions) > 0 ? json_decode($object->mandatory_questions, true) : [];

            if (in_array($questionId, $mandatoryArray)) {
                $mandatoryArray = array_diff($mandatoryArray, [$questionId]);
				$successMessage = $langs->trans('QuestionUnMandatorized', $questionRef);
			} else {
				$mandatoryArray[] = $questionId;
				$successMessage = $langs->trans('QuestionMandatorized', $questionRef);
			}

            $object->mandatory_questions = json_encode($mandatoryArray);
            $result = $object->update($user);

			if ($result > 0) {
				setEventMessage($successMessage);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
				header('Location: ' . $urltogo . '#questionList');
			} else {
                setEventMessages('', $object->errors, 'errors');
            }
        }
    }
}

/*
 * View
 */

$title    = $langs->trans('Sheet');
$help_url = 'FR:Module_DoliSMQ';

$elementArray = get_sheet_linkable_objects();

saturne_header(0,'', $title, $help_url);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewSheet'), '', 'object_' . $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate sheet-table">'."\n";

	//Label -- Libellé
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . GETPOST('label') . '">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//FK Element
	$linkableObject = 0;
	foreach ($elementArray as $key => $element) {
		if (!empty($element['conf'])) {
			print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
            $linkedObjects = empty(GETPOST("linked_object")) ? [] : GETPOST("linked_object");
			if ($conf->global->DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT) {
				print '<input type="radio" id="show_' . $key . '" name="linked_object[]" value="'.$key.'" '. (in_array($key, $linkedObjects) ? 'checked' : '') .'>';
			} else {
				print '<input type="checkbox" id="show_' . $key . '" name="linked_object[]" value="'.$key.'" '. (in_array($key, $linkedObjects) ? 'checked' : '') .'>';
			}
			print '</td></tr>';
			$linkableObject++;
		}
	}

	if ($linkableObject == 0) {
		print '<div class="wpeo-notice notice-warning notice-red">';
		print '<div class="notice-content">';
		print '<a href="' . dol_buildpath('/custom/dolismq/admin/sheet.php', 2) . '">' . '<b><div class="notice-subtitle">'.$langs->trans("ConfigElementLinked") . ' : ' . $langs->trans('ConfigSheet') . '</b></a>';
		print '</div>';
		print '</div>';
		print '</div>';
	}

	if (!empty($conf->categorie->enabled)) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('sheet', '', 'parent', 64, 0, 1);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'maxwidth500 widthcentpercentminusx');
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
	print load_fiche_titre($langs->trans('ModifySheet'), '', 'object_' . $object->picto);

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
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . $object->label . '">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
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
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, $object->getCategoriesCommon(436301002), '', 0, 'maxwidth500 widthcentpercentminusx');
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
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);
	saturne_banner_tab($object);

	$formconfirm = '';

	// SetLocked confirmation
	if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', '', 'yes', 'actionButtonClone', 350, 600);
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Delete') . ' ' . $langs->transnoentities('The' . ucfirst($object->element)), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = ['formConfirm' => $formconfirm];
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	unset($object->fields['label']); // Hide field already shown in banner

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'sheet', 1);
		print "</td></tr>";
	}

	$elementLinked = json_decode($object->element_linked);

	//FK Element
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

	$object->fetchQuestionsLinked($id, 'dolismq_' . $object->element);
	$questionIds = $object->linkedObjectsIds['dolismq_question'];
	if (is_array($questionIds) && !empty($questionIds)) {
		ksort($questionIds);
	}


	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">';
		$parameters = [];
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook) && $permissiontoadd) {
			// Create control
			if ($object->status == $object::STATUS_LOCKED) {
				print '<a class="butAction" id="actionButtonCreateControl" href="' . dol_buildpath('/custom/dolismq/view/control/control_card.php?action=create&fk_sheet=' . $object->id, 1) . '"><i class="fas fa-plus-circle"></i> ' . $langs->trans('CreateControl') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLocked', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-plus-circle"></i> ' . $langs->trans('CreateControl') . '</span>';
			}

			// Modify
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</span>';
			}

			// Lock
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonLock"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			}

			// Clone
			print '<span class="butAction" id="actionButtonClone"><i class="fas fa-clone"></i> ' . $langs->trans('Clone') . '</span>';

            // Archive
            if ($object->status == $object::STATUS_LOCKED) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_archive&token=' . newToken() . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</span>';
            }

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction('<i class="fas fa-trash"></i> ' . $langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>';
	}

	// QUESTIONS LINES
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans("LinkedQuestionsList"), '', '', 0, 'questionList');
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
		});
	</script>
	<?php
	// Lines
	print '<thead><tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('Label') . '</td>';
	print '<td>' . $langs->trans('Description') . '</td>';
	print '<td>' . $langs->trans('QuestionType') . '</td>';
  print '<td class="center">' . $langs->trans('Mandatory') . '</td>';
	print '<td class="center">' . $langs->trans('PhotoOk') . '</td>';
	print '<td class="center">' . $langs->trans('PhotoKo') . '</td>';
	print '<td>' . $langs->trans('Status') . '</td>';
	print '<td class="center">' . $langs->trans('Action') . '</td>';
	print '<td class="center"></td>';
	print '</tr></thead>';

	if (is_array($questionIds) && !empty($questionIds)) {
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
			print $langs->transnoentities($item->type);
			print '</td>';

      // Mandatory -- Rendre obligatoire
      $mandatoryArray = json_decode($object->mandatory_questions, true);

      print '<td class="center">';
      print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '">';
      print '<input type="hidden" name="token" value="' . newToken() . '">';
      print '<input type="hidden" name="action" value="set_mandatory">';
			print '<input type="hidden" name="questionId" value="'. $item->id . '">';
			print '<input type="hidden" name="questionRef" value="'. $item->ref . '">';
      print '<input type="checkbox" onchange="submit();" id="mandatory" name="mandatory" value="'. $item->id . '"' . (in_array($item->id, $mandatoryArray) ? ' checked ' : '') .  '" ' . ($object->status < Sheet::STATUS_LOCKED ? '>' : 'disabled>');
      print '</form>';
      print '</td>';

			print '<td>';

      print '<td class="center">';
			if (dol_strlen($item->photo_ok) > 0) {
				print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ok', 1, '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $item->ref . '/photo_ok', $item, 'photo_ok', 0, 0, 1,1);
			}
			print '</td>';

			print '<td class="center">';
			if (dol_strlen($item->photo_ko) > 0) {
				print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ko', 1, '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $item->ref . '/photo_ko', $item, 'photo_ko', 0, 0, 1,1);
			}
			print '</td>';

			print '<td>';
			print $item->getLibStatut(5);
			print '</td>';

			print '<td class="center">';
			if ($object->status < $object::STATUS_LOCKED) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=unlinkQuestion&questionId=' . $item->id . '">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			if ($object->status < $object::STATUS_LOCKED) {
				print '<td class="move-line ui-sortable-handle">';
			} else {
				print '<td>';
			}
			print '</td>';
			print '</tr>';
			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
		}
		print '</tr></tbody>';
	}

	if ($object->status < $object::STATUS_LOCKED) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addQuestion">';
		print '<input type="hidden" name="id" value="' . $id . '">';

		print '<tr class="add-line"><td class="">';
		print $question->selectQuestionList(0, 'questionId', 's.status = ' . Question::STATUS_LOCKED, '1', 0, 0, array(), '', 0, 0, 'disabled', '', false, $questionIds);
		print '</td>';
		print '<td>';
		print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Add") . '">';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '<td>';
		print '</td>';
		print '</tr>';

		print '</form>';
	}

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';

	$maxEvent = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $maxEvent, '', $morehtmlcenter);

	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
