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
 *   	\file       view/question/question_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view question
 */

// Load Dolibarr environment
if (file_exists("../../dolismq.main.inc.php")) $res = @include "../../dolismq.main.inc.php";

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once '../../class/question.class.php';
require_once '../../core/modules/dolismq/question/mod_question_standard.php';
require_once '../../lib/dolismq_question.lib.php';
require_once '../../lib/dolismq_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user, $langs;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'questioncard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize objects
// Technical objets
$object         = new Question($db);
$extrafields    = new ExtraFields($db);
$refQuestionMod = new $conf->global->DOLISMQ_QUESTION_ADDON($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('questioncard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$permissiontoread   = $user->rights->dolismq->question->read;
$permissiontoadd    = $user->rights->dolismq->question->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->question->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

// Security check - Protection if external user
saturne_check_access($module, $object, $permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolismq/view/question/question_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/question/question_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'add' && !empty($permissiontoadd)) {
		foreach ($object->fields as $key => $val) {
			if ($object->fields[$key]['type'] == 'duration') {
				if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') {
					continue; // The field was not submited to be edited
				}
			} else {
				if (!GETPOSTISSET($key)) {
					continue; // The field was not submited to be edited
				}
			}
			// Ignore special fields
			if (in_array($key, array('rowid', 'entity', 'import_key'))) {
				continue;
			}
			if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
				if (!in_array(abs($val['visible']), array(1, 3))) {
					continue; // Only 1 and 3 that are case to create
				}
			}

			// Set value to insert
			if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
				$value = GETPOST($key, 'restricthtml');
			} elseif ($object->fields[$key]['type'] == 'date') {
				$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));	// for date without hour, we use gmt
			} elseif ($object->fields[$key]['type'] == 'datetime') {
				$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
			} elseif ($object->fields[$key]['type'] == 'duration') {
				$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
			} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
				$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
			} elseif ($object->fields[$key]['type'] == 'boolean') {
				$value = ((GETPOST($key) == '1' || GETPOST($key) == 'on') ? 1 : 0);
			} elseif ($object->fields[$key]['type'] == 'reference') {
				$tmparraykey = array_keys($object->param_list);
				$value = $tmparraykey[GETPOST($key)].','.GETPOST($key.'2');
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
			if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
				$value = ''; // This is an implicit foreign key field
			}
			if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
				$value = ''; // This is an explicit foreign key field
			}

			//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
			$object->$key = $value;
			if ($val['notnull'] > 0 && $object->$key == '' && !is_null($val['default']) && $val['default'] == '(PROV)') {
				$object->$key = '(PROV)';
			}
			if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
			}
		}

		// Fill array 'array_options' with data from add form
		if (!$error) {
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
		}

		if ( ! $error && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
			// Define relativepath and upload_dir
			$relativepath                                             = '/question/tmp/QU0/photo_ok';
			$upload_dir                                               = $conf->dolismq->multidir_output[$conf->entity] . '/' . $relativepath;
			if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
			else $userfiles                                           = array($_FILES['userfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['userfile']['tmp_name'][$key])) {
					if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					}
					if ($_FILES['userfile']['error'][$key] == 4) {
						$error++;
					}
				}
			}

			if ( ! $error) {
				$generatethumbs = 1;
				dol_add_file_process($upload_dir, 0, 1, 'userfile', '', null, '', $generatethumbs);
			}
			$error = 0;
		}

		if ( ! $error && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
			// Define relativepath and upload_dir
			$relativepath                                             = '/question/tmp/QU0/photo_ko';
			$upload_dir                                               = $conf->dolismq->multidir_output[$conf->entity] . '/' . $relativepath;
			if (is_array($_FILES['userfile2']['tmp_name'])) $userfiles = $_FILES['userfile2']['tmp_name'];
			else $userfiles                                           = array($_FILES['userfile2']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['userfile2']['tmp_name'][$key])) {
					if ($_FILES['userfile2']['error'][$key] == 1 || $_FILES['userfile2']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					}
					if ($_FILES['userfile']['error'][$key] == 4) {
						$error++;
					}
				}
			}

			if ( ! $error) {
				$generatethumbs = 1;
				dol_add_file_process($upload_dir, 0, 1, 'userfile2', '', null, '', $generatethumbs);
			}
			$error = 0;
		}

		if (!$error) {
			$types = array('photo_ok', 'photo_ko');

			foreach ($types as $type) {
				$pathToTmpPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/' . $type;
				$photo_list = dol_dir_list($conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/' . 'QU0/' . $type);
				if ( ! empty($photo_list)) {
					foreach ($photo_list as $file) {
						if ($file['type'] !== 'dir') {
							$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $refQuestionMod->getNextValue($object);
							if (!is_dir($pathToQuestionPhoto)) {
								mkdir($pathToQuestionPhoto);
							}
							$pathToQuestionPhotoType = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $refQuestionMod->getNextValue($object) . '/' . $type;
							if (!is_dir($pathToQuestionPhotoType)) {
								mkdir($pathToQuestionPhotoType);
							}

							copy($file['fullname'], $pathToQuestionPhotoType . '/' . $file['name']);

							global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
							$destfull = $pathToQuestionPhotoType . '/' . $file['name'];

							if (empty($object->$type)) {
								$object->$type = $file['name'];
							}

							// Create thumbs
							// We can't use $object->addThumbs here because there is no $object known
							// Used on logon for example
							$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
							// Create mini thumbs for image (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
							unlink($file['fullname']);
						}
					}
				}
				$filesThumbs = dol_dir_list($pathToTmpPhoto . '/thumbs/');
				if ( ! empty($filesThumbs)) {
					foreach ($filesThumbs as $fileThumb) {
						unlink($fileThumb['fullname']);
					}
				}
			}

			$result = $object->create($user);
			if ($result > 0) {
				// Creation OK
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);
				$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: ".$urltogo);
				exit;
			} else {
				// Creation KO
				if (!empty($object->errors)) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					setEventMessages($langs->trans($object->error), null, 'errors');
				}
				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
		if (!($object->id > 0)) {
			dol_print_error('', 'Error, object must be fetched before being deleted');
			exit;
		}
		$categories = $object->getCategoriesCommon('question');

		if (is_array($categories) && !empty($categories)) {
			foreach ($categories as $cat_id) {

				$category = new Categorie($db);
				$category->fetch($cat_id);
				$category->del_type($object, 'question');
			}
		}

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
	if ($action == 'confirm_setLocked') {
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
}

/*
 * View
 */

$title    = $langs->trans("Question");
$help_url = '';
$morejs   = array("/dolismq/js/dolismq.js");
$morecss  = array("/dolismq/css/dolismq.css");

saturne_header($module, $action, $subaction, 1,'', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewQuestion'), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="createQuestionForm" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate question-table">'."\n";

	// Ref -- Ref
//	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td>';
//	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="' . $refQuestionMod->getNextValue($object) . '">';
//	print $refQuestionMod->getNextValue($object);
//	print '</td></tr>';

	// Label -- Libellé
	print '<tr><td class="">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.GETPOST('label').'">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="fieldrequired" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', '', '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// EnterComment -- Saisir les commentaires
	print '<tr><td class="minwidth400">' . $langs->trans("EnterComment") . '</td><td>';
	print '<input type="checkbox" id="enter_comment" name="enter_comment"' . (GETPOST('enter_comment') ? ' checked=""' : '') . '>';
	print $form->textwithpicto('', $langs->trans('EnterCommentTooltip'));
	print '</td></tr>';

	// AuthorizeAnswerPhoto -- Utiliser des réponses de photos
	print '<tr><td class="minwidth400">' . $langs->trans("AuthorizeAnswerPhoto") . '</td><td>';
	print '<input type="checkbox" id="authorize_answer_photo" name="authorize_answer_photo"' . (GETPOST('authorize_answer_photo') ? ' checked=""' : '') . '>';
	print $form->textwithpicto('', $langs->trans('AuthorizeAnswerPhotoTooltip'));
	print '</td></tr>';

	// ShowPhoto -- Utiliser des photos
	print '<tr><td class="minwidth400">' . $langs->trans("ShowPhoto") . '</td><td>';
	print '<input type="checkbox" id="show_photo" name="show_photo"' . (GETPOST('show_photo') ? ' checked=""' : '') . '>';
	print $form->textwithpicto('', $langs->trans('ShowPhotoTooltip'));
	print '</td></tr>';

	// Photo OK -- Photo OK
	print '<tr class="linked-medias photo_ok hidden" ' . (GETPOST('show_photo') ? '' : 'style="display:none"') . '><td class=""><label for="photo_ok">' . $langs->trans("PhotoOk") . '</label></td><td class="linked-medias-list">'; ?>
	<input hidden multiple class="fast-upload" id="fast-upload-photo-ok" type="file" name="userfile[]" capture="environment" accept="image/*">
	<label for="fast-upload-photo-ok">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ok" name="photo_ok" value="<?php echo GETPOST('favorite_photo_ok') ?>"/>
	<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="modal-to-open" value="media_gallery"/>
		<input type="hidden" class="from-type" value="question"/>
		<input type="hidden" class="from-subtype" value="photo_ok"/>
		<input type="hidden" class="from-subdir" value="photo_ok"/>
		<input type="hidden" class="from-id" value="<?php echo 0 ?>"/>
		<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
	</div>
	<?php
	$relativepath = 'dolismq/medias/thumbs';
	print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/photo_ok', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/tmp/QU0/photo_ok', $object, 'photo_ok', 1, $permissiontodelete);
	print '</td></tr>';

	print '<tr></tr>';

	// Photo KO -- Photo KO
	print '<tr class="linked-medias photo_ko hidden" ' . (GETPOST('show_photo') ? '' : 'style="display:none"') . '><td class=""><label for="photo_ko">' . $langs->trans("PhotoKo") . '</label></td><td class="linked-medias-list">'; ?>
	<input hidden multiple class="fast-upload" id="fast-upload-photo-ko" type="file" name="userfile[]" capture="environment" accept="image/*">
	<label for="fast-upload-photo-ko">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ko" name="photo_ko" value="<?php echo GETPOST('favorite_photo_ko') ?>"/>
	<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="modal-to-open" value="media_gallery"/>
		<input type="hidden" class="from-type" value="question"/>
		<input type="hidden" class="from-subtype" value="photo_ko"/>
		<input type="hidden" class="from-subdir" value="photo_ko"/>
		<input type="hidden" class="from-id" value="<?php echo 0 ?>"/>
		<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
	</div>
	<?php
	print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/photo_ko', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/tmp/QU0/photo_ko', $object, 'photo_ko', 1, $permissiontodelete);
	print '</td></tr>';

	// Categories
	if (!empty($conf->categorie->enabled)) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('question', '', 'parent', 64, 0, 1);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'maxwidth500 widthcentpercentminusx');
		print "</td></tr>";
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print ' &nbsp; <input type="button" id ="actionButtonCancelCreate" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

	dol_set_focus('input[name="label"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ModifyQuestion"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit question-table">'."\n";

	// Ref -- Ref
	print '<tr><td class="fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	print '<tr><td><label class="fieldrequired" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// EnterComment -- Saisir les commentaires
	print '<tr class="oddeven"><td class="minwidth400">';
	print $langs->trans("EnterComment");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="enter_comment" name="enter_comment"' . ($object->enter_comment ? ' checked=""' : '') . '"> ';
	print $form->textwithpicto('', $langs->trans('EnterCommentTooltip'));
	print '</td></tr>';

	// AuthorizeAnswerPhoto -- Utiliser les réponses de photos
	print '<tr class="oddeven"><td class="minwidth400">';
	print $langs->trans("AuthorizeAnswerPhoto");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="authorize_answer_photo" name="authorize_answer_photo"' . ($object->authorize_answer_photo ? ' checked=""' : '') . '"> ';
	print $form->textwithpicto('', $langs->trans('AuthorizeAnswerPhotoTooltip'));
	print '</td></tr>';

	// ShowPhoto -- Utiliser les photos
	print '<tr class="oddeven"><td class="minwidth400">';
	print $langs->trans("ShowPhoto");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_photo" name="show_photo"' . ($object->show_photo ? ' checked=""' : '') . '"> ';
	print $form->textwithpicto('', $langs->trans('ShowPhotoTooltip'));
	print '</td></tr>';

	// Photo OK -- Photo OK
	print '<tr class="' . ($object->show_photo ? ' linked-medias photo_ok' : ' linked-medias photo_ok hidden' ) . '" style="' . ($object->show_photo ? ' ' : ' display:none') . '"><td><label for="photo_ok">' . $langs->trans("PhotoOk") . '</label></td><td class="linked-medias-list">'; ?>
	<input hidden multiple class="fast-upload" id="fast-upload-photo-ok" type="file" name="userfile[]" capture="environment" accept="image/*">
	<label for="fast-upload-photo-ok">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ok" name="photo_ok" value="<?php echo (dol_strlen($object->photo_ok) > 0 ? $object->photo_ok : GETPOST('favorite_photo_ok')) ?>"/>
	<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="modal-to-open" value="media_gallery"/>
		<input type="hidden" class="from-type" value="question"/>
		<input type="hidden" class="from-subtype" value="photo_ok"/>
		<input type="hidden" class="from-subdir" value="photo_ok"/>
		<input type="hidden" class="from-id" value="<?php echo $object->id ?>"/>
		<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
	</div>
	<?php
	$relativepath = 'dolismq/medias/thumbs';
	print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ok', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $object->ref . '/photo_ok', $object, 'photo_ok', 1, $permissiontodelete);
	print '</td></tr>';

	print '<tr></tr>';

	// Photo KO -- Photo KO
	print '<tr class="' . ($object->show_photo ? ' linked-medias photo_ko' : ' linked-medias photo_ko hidden' ) . '" style="' . ($object->show_photo ? ' ' : ' display:none') . '"><td><label for="photo_ko">' . $langs->trans("PhotoKo") . '</label></td><td class="linked-medias-list">'; ?>
	<input hidden multiple class="fast-upload" id="fast-upload-photo-ko" type="file" name="userfile[]" capture="environment" accept="image/*">
	<label for="fast-upload-photo-ko">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ko" name="photo_ko" value="<?php echo (dol_strlen($object->photo_ko) > 0 ? $object->photo_ko : GETPOST('favorite_photo_ko')) ?>"/>
	<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="modal-to-open" value="media_gallery"/>
		<input type="hidden" class="from-type" value="question"/>
		<input type="hidden" class="from-subtype" value="photo_ko"/>
		<input type="hidden" class="from-subdir" value="photo_ko"/>
		<input type="hidden" class="from-id" value="<?php echo $object->id ?>"/>
		<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
	</div>
	<?php
	print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ko', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $object->ref . '/photo_ko', $object, 'photo_ko', 1, $permissiontodelete);
	print '</td></tr>';

	// Tags-Categories
	if ($conf->categorie->enabled) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('question', '', 'parent', 64, 0, 1);
		$c = new Categorie($db);
		$cats = $c->containing($object->id, 'question');
		$arrayselected = array();
		if (is_array($cats)) {
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
		}
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'maxwidth500 widthcentpercentminusx');
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

	$head = questionPrepareHead($object);
	print dol_get_fiche_head($head, 'questionCard', $langs->trans("Question"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteQuestion'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockQuestion'), $langs->trans('ConfirmLockQuestion', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js

		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneQuestion', $object->ref), 'confirm_clone', '', 'yes', 'actionButtonClone', 350, 600);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/dolismq/view/question/question_list.php', 1).'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	dol_strlen($object->label) ? $morehtmlref .= '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '</div>';

	$object->picto = 'question_small@dolismq';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	//Description -- Description
	print '<tr><td class="titlefield">';
	print $langs->trans("Description");
	print '</td>';
	print '<td>';
	print $object->description;
	print '</td></tr>';

	// EnterComment -- Saisir les commentaires
	print '<tr><td class="titlefield">';
	print $langs->trans("EnterComment");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="enter_comment" name="enter_comment"' . ($object->enter_comment ? ' checked=""' : '') . '" disabled> ';
	print '</td></tr>';

	// AuthorizeAnswerPhoto -- Utiliser les réponses de photos
	print '<tr><td class="titlefield">';
	print $langs->trans("AuthorizeAnswerPhoto");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="authorize_answer_photo" name="authorize_answer_photo"' . ($object->authorize_answer_photo ? ' checked=""' : '') . '" disabled> ';
	print '</td></tr>';

	// ShowPhoto -- Utiliser les photos
	print '<tr><td class="titlefield">';
	print $langs->trans("ShowPhoto");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_photo" name="show_photo"' . ($object->show_photo ? ' checked=""' : '') . '" disabled> ';
	print '</td></tr>';

	if ($object->show_photo > 0) {
		//Photo OK -- Photo OK
		print '<tr><td class="titlefield">';
		print $langs->trans("PhotoOk");
		print '</td>';
		print '<td>';
		print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ok', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $object->ref . '/photo_ok', $object, 'photo_ok', 0, 0, 0,1);
		print '</td></tr>';

		//Photo KO -- Photo KO
		print '<tr><td class="titlefield">';
		print $langs->trans("PhotoKo");
		print '</td>';
		print '<td>';
		print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ko', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/'. $object->ref . '/photo_ko', $object, 'photo_ko', 0, 0, 0,1);
		print '</td></tr>';
	}

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'question', 1);
		print "</td></tr>";
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Back to draft
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1) ? 'actionButtonLock' : '') . '">' . $langs->trans("Lock") . '</span>';
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit', '', $permissiontoadd);
			}

			print '<span class="butAction" id="actionButtonClone" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone' . '">' . $langs->trans("ToClone") . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			}
		}
		print '</div>'."\n";
	}

	print '<div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', dol_buildpath('/dolismq/view/question/question_agenda.php', 1).'?id='.$object->id);

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
