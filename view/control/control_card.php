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
 *   	\file       view/control/control_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view control
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT']. '/main.inc.php';
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)). '/main.inc.php')) $res = @include substr($tmp, 0, ($i + 1)). '/main.inc.php';
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php')) $res = @include dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php';
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) $res = @include '../main.inc.php';
if ( ! $res && file_exists('../../main.inc.php')) $res       = @include '../../main.inc.php';
if ( ! $res && file_exists('../../../main.inc.php')) $res    = @include '../../../main.inc.php';
if ( ! $res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/control/mod_control_standard.php';
require_once __DIR__ . '/../../core/modules/dolismq/controldet/mod_controldet_standard.php';
require_once __DIR__ . '/../../lib/dolismq_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array('dolismq@dolismq', 'other'));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'controlcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize objects
// Technical objets
$object           = new Control($db);
$controldet       = new ControlLine($db);
$sheet            = new Sheet($db);
$question         = new Question($db);
$usertmp          = new User($db);
$product          = new Product($db);
$project          = new Project($db);
$task             = new Task($db);
$thirdparty       = new Societe($db);
$productlot       = new Productlot($db);
$extrafields      = new ExtraFields($db);
$ecmfile 		  = new EcmFiles($db);
$category         = new Categorie($db);
$refControlMod    = new $conf->global->DOLISMQ_CONTROL_ADDON($db);
$refControlDetMod = new $conf->global->DOLISMQ_CONTROLDET_ADDON($db);

// View objects
$form        = new Form($db);
$formproject = new FormProjets($db);

$hookmanager->initHooks(array('controlcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread   = $user->rights->dolismq->control->read;
$permissiontoadd    = $user->rights->dolismq->control->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->control->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
if ($user->socid > 0) accessforbidden();
if ($user->socid > 0) $socid = $user->socid;
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolismq/view/control/control_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/control/control_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ( ! $error && $action == 'addFiles') {
		$data = json_decode(file_get_contents('php://input'), true);

		$filenames  = $data['filenames'];
		$questionId = $data['questionId'];
		$type 	    = $data['type'];

		$object->fetch($id);
		$question->fetch($questionId);
		if (dol_strlen($object->ref) > 0) {
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/' .  $type;
			dol_mkdir($pathToQuestionPhoto);
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/' .  $type . '/' . $question->ref;
			dol_mkdir($pathToQuestionPhoto);
		} else {
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/control/'. $object->ref . 'tmp/' . 'QU0/' . $type ;
		}

		if (preg_match('/vVv/', $filenames)) {
			$filenames = preg_split('/vVv/', $filenames);
			array_pop($filenames);
		} else {
			$filenames = array($filenames);
		}


		if ( ! (empty($filenames))) {
			if ( ! is_dir($conf->dolismq->multidir_output[$conf->entity] . '/control/tmp/')) {
				dol_mkdir($conf->dolismq->multidir_output[$conf->entity] . '/control/tmp/');
			}

			if ( ! is_dir($conf->dolismq->multidir_output[$conf->entity] . '/control/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/QU0') )) {
				dol_mkdir($conf->dolismq->multidir_output[$conf->entity] . '/control/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/QU0'));
			}

			foreach ($filenames as $filename) {
				$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';

				if (is_file($conf->ecm->multidir_output[$conf->entity] . '/dolismq/medias/' . $filename)) {
					$pathToECMPhoto = $conf->ecm->multidir_output[$conf->entity] . '/dolismq/medias/' . $filename;

//					if ( ! is_dir($pathToQuestionPhoto)) {
//						mkdir($pathToQuestionPhoto);
//					}

					copy($pathToECMPhoto, $pathToQuestionPhoto . '/' . $filename);
					$ecmfile->fetch(0,'',(($conf->entity > 1) ? $conf->entity.'/ecm/dolismq/medias/' : 'ecm/dolismq/medias/') . $filename);
					$date = dol_print_date(dol_now(),'dayxcard');
					$extension = preg_split('/\./', $filename);
					$newFilename = $conf->entity . '_' . $ecmfile->id . '_' . $object->ref . '_' . $question->ref . '_' . $date . '.' . $extension[1];
					rename($pathToQuestionPhoto . '/' . $filename, $pathToQuestionPhoto . '/' . $newFilename);

					global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
					$destfull = $pathToQuestionPhoto . '/' . $newFilename;

					// Create thumbs
					$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, 'thumbs');
					// Create mini thumbs for image (Ratio is near 16/9)
					$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, 'thumbs');
				}
			}
		}
	}

	if ( ! $error && $action == 'unlinkFile' && $permissiontodelete) {
		$data = json_decode(file_get_contents('php://input'), true);

		$filename    = $data['filename'];
		$splitFilename = preg_split('/_/', $filename);

		$object->fetch($id);
		$question->fetch(0,$splitFilename[3]);

		$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $question->ref;

		$files = dol_dir_list($pathToQuestionPhoto);

		foreach ($files as $file) {
			if (is_file($file['fullname']) && $file['name'] == $filename) {
				unlink($file['fullname']);
			}
		}

		$files = dol_dir_list($pathToQuestionPhoto . '/thumbs');
		foreach ($files as $file) {
			if (preg_match('/' . preg_split('/\./', $filename)[0] . '/', $file['name'])) {
				unlink($file['fullname']);
			}
		}

		$urltogo = str_replace('__ID__', $id, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header('Location: ' . $urltogo);
		exit;
	}

	if ($action == 'classin' && $permissiontoadd) {
		// Link to a project
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'save') {

		$controldet = new ControlLine($db);
		$sheet->fetch($object->fk_sheet);
		$object->fetchObjectLinked($sheet->id, 'dolismq_sheet');
		$questionIds = $object->linkedObjectsIds['dolismq_question'];

		foreach ($questionIds as $questionId) {
			$controldettmp = $controldet;
			//fetch controldet avec le fk_question et fk_control, s'il existe on l'update sinon on le crée
			$result = $controldettmp->fetchFromParentWithQuestion($object->id, $questionId);

			if ($result > 0 && is_array($result)) {
				$controldettmp = array_shift($result);
				//sauvegarder réponse
				$answer = GETPOST('answer'.$questionId);

				if ($answer > 0) {
					$controldettmp->answer = $answer;
				}

				//sauvegarder commentaire
				$comment = GETPOST('comment'.$questionId);

				if (dol_strlen($comment) > 0) {
					$controldettmp->comment = $comment;
				}

				$question->fetch($questionId);
				$controldettmp->update($user);
			} else {
				$controldettmp = $controldet;

				$controldettmp->ref = $refControlDetMod->getNextValue($controldettmp);

				$controldettmp->fk_control  = $object->id;
				$controldettmp->fk_question = $questionId;

				//sauvegarder réponse
				$answer = GETPOST('answer'.$questionId);

				if ($answer > 0) {
					$controldettmp->answer = $answer;
				} else {
					$controldettmp->answer = '';
				}

				//sauvegarder commentaire
				$comment = GETPOST('comment'.$questionId);
				if (dol_strlen($comment) > 0) {
					$controldettmp->comment = $comment;
				} else {
					$controldettmp->comment = '';
				}

				$question->fetch($questionId);

				$controldettmp->entity = $conf->entity;
				$controldettmp->insert($user);
			}
		}

		setEventMessages($langs->trans('AnswerSaved'), array());
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

	if (!$error && $action == 'confirm_delete' && $permissiontodelete) {
		$db->begin();

		$objecttmp = $object;
		$nbok = 0;
		$TMsg = array();
		$result = $objecttmp->fetch($id);

		if ($result > 0) {
			$objecttmp->fetchObjectLinked('','',$id, 'dolismq_' . $object->element);
			$objecttmp->element = 'dolismq_' . $objecttmp->element;
			if (is_array($objecttmp->linkedObjects) && !empty($objecttmp->linkedObjects)) {
				foreach($objecttmp->linkedObjects as $linkedObjectType => $linkedObjectArray) {
					foreach($linkedObjectArray as $linkedObject) {
						if (method_exists($objecttmp, 'is_erasable') && $objecttmp->is_erasable() <= 0) {
							$objecttmp->deleteObjectLinked($linkedObject->id, $linkedObjectType);
						}
					}
				}
			}

			$result = $objecttmp->delete($user);

			if ($result > 0) {
				$db->commit();

				// Delete OK
				setEventMessages('RecordDeleted', null, 'mesgs');

				header('Location: ' .$backurlforlist);
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
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
		}



		//var_dump($listofobjectthirdparties);exit;
	}

	// Action to build doc
	if ($action == 'builddoc' && $permissiontoadd) {
		if (is_numeric(GETPOST('model', 'alpha'))) {
			$error = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Model'));
		} else {
			// Reload to get all modified line records and be ready for hooks
			$ret = $object->fetch($id);
			$ret = $object->fetch_thirdparty();
			/*if (empty($object->id) || ! $object->id > 0)
			{
				dol_print_error('Object must have been loaded by a fetch');
				exit;
			}*/

			// Save last template used to generate document
//			if (GETPOST('model', 'alpha')) {
//				$object->setDocModel($user, GETPOST('model', 'alpha'));
//			}

			// Special case to force bank account
			//if (property_exists($object, 'fk_bank'))
			//{
			if (GETPOST('fk_bank', 'int')) {
				// this field may come from an external module
				$object->fk_bank = GETPOST('fk_bank', 'int');
			} elseif (!empty($object->fk_account)) {
				$object->fk_bank = $object->fk_account;
			}
			//}

			$outputlangs = $langs;
			$newlang = '';

			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->thirdparty->default_lang)) {
				$newlang = $object->thirdparty->default_lang; // for proposal, order, invoice, ...
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->default_lang)) {
				$newlang = $object->default_lang; // for thirdparty
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			// To be sure vars is defined
			if (empty($hidedetails)) {
				$hidedetails = 0;
			}
			if (empty($hidedesc)) {
				$hidedesc = 0;
			}
			if (empty($hideref)) {
				$hideref = 0;
			}
			if (empty($moreparams)) {
				$moreparams = null;
			}

			$moreparams['object'] = $object;

			$result = $object->generateDocument(GETPOST('model'), $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			} else {
				if (empty($donotredirect)) {	// This is set when include is done by bulk action "Bill Orders"
					setEventMessages($langs->trans('FileGenerated'), null);

					$urltoredirect = $_SERVER['REQUEST_URI'];
					$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
					$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

					header('Location: '.$urltoredirect.'#builddoc');
					exit;
				}
			}
		}
	}

	if ($action == 'confirm_setVerdict' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		$object->fetch($id);
		if ( ! $error) {
			$object->verdict = GETPOST('verdict', 'int');
			$object->note_public = GETPOST('noteControl');
			$result = $object->update($user);
			if ($result > 0) {
				// Set verdict Control
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set verdict Control error
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $permissiontodelete) {
		if ( ! empty($upload_dir)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

			$langs->load('other');
			$filetodelete = GETPOST('file', 'alpha');
			$file         = $upload_dir . '/' . $filetodelete;
			$ret          = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessages($langs->trans('FileWasRemoved', $filetodelete), null, 'mesgs');
			else setEventMessages($langs->trans('ErrorFailToDeleteFile', $filetodelete), null, 'errors');

			// Make a redirect to avoid to keep the remove_file into the url that create side effects
			$urltoredirect = $_SERVER['REQUEST_URI'];
			$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
			$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

			header('Location: ' . $urltoredirect);
			exit;
		} else {
			setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
		}
	}

	// Action to set status STATUS_VALIDATED
	if ($action == 'confirm_setValidated') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setValidated($user, false);
			if ($result > 0) {
				$controldet = new ControlLine($db);
				$sheet->fetch($object->fk_sheet);
				$object->fetchObjectLinked($sheet->id, 'dolismq_sheet');
				$questionIds = $object->linkedObjectsIds;
				foreach ($questionIds['dolismq_question'] as $questionId) {
					$controldettmp = $controldet;
					//fetch controldet avec le fk_question et fk_control, s'il existe on l'update sinon on le crée
					$result = $controldettmp->fetchFromParentWithQuestion($object->id, $questionId);

					if ($result > 0 && is_array($result)) {
						$controldettmp = array_shift($result);
						//sauvegarder réponse
						$answer = GETPOST('answer'.$questionId);
						if ($answer > 0) {
							$controldettmp->answer = $answer;
						}

						//sauvegarder commentaire
						$comment = GETPOST('comment'.$questionId);

						if (dol_strlen($comment) > 0) {
							$controldettmp->comment = $comment;
						}

						$controldettmp->update($user);
					} else {
						$controldettmp->fk_control  = $object->id;
						$controldettmp->fk_question = $questionId;

						//sauvegarder réponse
						$answer = GETPOST('answer'.$questionId);
						if ($answer > 0) {
							$controldettmp->answer = $answer;
						}

						//sauvegarder commentaire
						$comment = GETPOST('comment'.$questionId);
						if (dol_strlen($comment) > 0) {
							$controldettmp->comment = $comment;
						}
						$controldettmp->entity = $conf->entity;

						$controldettmp->insert($user);
					}
				}
				// Set validated OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set validated KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_REOPENED
	if ($action == 'confirm_setReopened') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setDraft($user, false);
			if ($result > 0) {
				$object->verdict = null;
				$result = $object->update($user);
				// Set reopened OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set reopened KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_setLocked') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Actions to send emails
	$triggersendname = 'CONTROLDOCUMENT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_AUDIT_TO';
	$trackid = 'control'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$title         = $langs->trans('Control');
$title_create  = $langs->trans('NewControl');
$title_edit    = $langs->trans('ModifyControl');
$help_url      = '';
$morejs        = array('/dolismq/js/dolismq.js');
$morecss       = array('/dolismq/css/dolismq.css');

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', 'object_'.$object->picto);

	print '<form method="POST" id="createControlForm" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate control-table"><thead>'."\n";

	if (!empty(GETPOST('fk_sheet'))) {
		$sheet->fetch(GETPOST('fk_sheet'));
	}

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Categories
	if (!empty($conf->categorie->enabled)) {
		print '<tr><td>'.$langs->trans('Categories').'</td><td>';
		$cate_arbo = $form->select_all_categories('control', '', 'parent', 64, 0, 1);
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'maxwidth500 widthcentpercentminusx');
		print '</td></tr>';
	}

	//FK SHEET
	print '<tr><td class="fieldrequired">' . $langs->trans('SheetLinked') . '</td><td>';
	print '<i class="fas fa-list"></i>' . $sheet->select_sheet_list(GETPOST('fk_sheet')?: $sheet->id);
	print '</td></tr></thead>';

	print '<tr><td><hr></td><td>';
	print '<hr>';

	print '<div class="fields-content">';

	//FK Product
	if ($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT && preg_match('/"product":1/',$sheet->element_linked)) {
		print '<tr><td class="">' . $langs->trans('Product') . ' ' . $langs->trans('Or') . ' ' . $langs->trans('Service') . '</td><td>';
		$events = array();
		$events[1] = array('method' => 'getProductLots', 'url' => dol_buildpath('/custom/dolismq/core/ajax/lots.php?showempty=1', 1), 'htmlname' => 'fk_productlot');
		print img_picto('', 'product') . $form->select_produits(GETPOST('fk_product'), 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProductsOrServices', 0, 'minwidth500');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddThirdParty') . '"></span></a>';
		print '</td></tr>';
	}

	//FK PRODUCTLOT
	if ($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT && preg_match('/"productlot":1/',$sheet->element_linked)) {
		print '<tr><td class="">';
		print $langs->trans('Lot');
		print '</td><td class="lot-container">';
		print '<span class="lot-content">';
		$data = json_decode(file_get_contents('php://input'), true);
		dol_strlen($data['productRef']) > 0 ? $product->fetch(0, $data['productRef']) : 0;
		print img_picto('', 'lot') . dolismq_select_product_lots((!empty(GETPOST('fk_product')) ? GETPOST('fk_product') : $product->id), GETPOST('fk_productlot'), 'fk_productlot', 1, '', '', 0, 'minwidth500', false, 0, array(), false, '', 'fk_productlot');
		print '</span>';
		print '</td></tr>';
	}

	//FK Soc
	if ($conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY && preg_match('/"thirdparty":1/',$sheet->element_linked)) {
		print '<tr><td class="">' . $langs->trans('ThirdPartyLinked') . '</td><td>';
		print img_picto('', 'building') . $form->select_company(GETPOST('fk_soc'), 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), 0, 'minwidth500');
		print ' <a class="butActionNew" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddThirdParty') . '"></span></a>';
		print '</td></tr>';
	}

	//FK Project
	if ($conf->global->DOLISMQ_CONTROL_SHOW_PROJECT && preg_match('/"project":1/',$sheet->element_linked)) {
		print '<tr><td class="">' . $langs->trans('ProjectLinked') . '</td><td>';
		print img_picto('', 'project') . $formproject->select_projects((!empty(GETPOST('fk_soc')) ? GETPOST('fk_soc') : -1), GETPOST('projectid'), 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'minwidth500');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/projet/card.php?socid=' . GETPOST('fk_soc') . '&action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProject') . '"></span></a>';
		print '</td></tr>';
	}

	//FK Task
	if ($conf->global->DOLISMQ_CONTROL_SHOW_TASK && preg_match('/"task":1/',$sheet->element_linked)) {
		print '<tr><td class="">' . $langs->trans('TaskLinked');
		print '</td><td class="task-container">';
		print '<span class="task-content">';
		$data = json_decode(file_get_contents('php://input'), true);
		dol_strlen($data['projectRef']) > 0 ? $project->fetch(0, $data['projectRef']) : 0;
		img_picto('', 'projecttask') . $formproject->selectTasks((!empty(GETPOST('fk_soc')) ? GETPOST('fk_soc') : 0), GETPOST('fk_task'), 'fk_task', 24, 0, '1', 1, 0, 0, 'minwidth500', (!empty(GETPOST('projectid')) ? GETPOST('fk_project') : $project->id), '');
		print '</span>';
		print '</td></tr>';
	}
	print '</div>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Create');

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = controlPrepareHead($object);
	print dol_get_fiche_head($head, 'controlCard', $langs->trans('Control'), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteControl'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	if ($action == 'setVerdict') {
		//Form to close proposal (signed or not)

		$AllAnswer = $controldet->fetchFromParent($object->id);
		$answerOK = 0; $answerKO = 0; $answerRepair = 0; $answerNotApplicable = 0;
		if (!empty($AllAnswer)) {
			foreach ($AllAnswer as $answer){
				switch ($answer->answer){
					case 1:
						$answerOK++;
						break;
					case 2:
						$answerKO++;
						break;
					case 3:
						$answerRepair++;
						break;
					case 4:
						$answerNotApplicable++;
						break;
				}
			}
		}

		$formquestion = array(
			array('type' => 'select', 'name' => 'verdict', 'label' => '<span class="fieldrequired">' . $langs->trans('VerdictControl') . '</span>', 'values' => array('1' => 'OK', '2' => 'KO'), 'select_show_empty' => 0),
			array('type' => 'text', 'name' => 'OK', 'label' => '<span class="answer" value="1" style="pointer-events: none"><i class="fas fa-check"></i></span>', 'value' => $answerOK, 'moreattr' => 'readonly'),
			array('type' => 'text', 'name' => 'KO', 'label' => '<span class="answer" value="2" style="pointer-events: none"><i class="fas fa-times"></i></span>', 'value' => $answerKO, 'moreattr' => 'readonly'),
			array('type' => 'text', 'name' => 'Repair', 'label' => '<span class="answer" value="3" style="pointer-events: none"><i class="fas fa-tools"></i></span>', 'value' => $answerRepair, 'moreattr' => 'readonly'),
			array('type' => 'text', 'name' => 'NotApplicable', 'label' => '<span class="answer" value="4" style="pointer-events: none">N/A</span>', 'value' => $answerNotApplicable, 'moreattr' => 'readonly'),
			array('type' => 'text', 'name' => 'noteControl', 'label' => '<div class="note-control" style="margin-top: 20px;">' . $langs->trans('NoteControl') . '</div>'),
		);

		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('SetOK/KO'), $text, 'confirm_setVerdict', $formquestion, '', 1, 300);
	}

	// SetValidated confirmation
	if (($action == 'setValidated')) {
		// a mettre après le confirm
		$controldet = new ControlLine($db);
		$sheet->fetch($object->fk_sheet);
		$object->fetchObjectLinked($sheet->id, 'dolismq_sheet');
		$questionIds = $object->linkedObjectsIds;

		if (!empty($questionIds)) {
			$questionCounter = count($questionIds['dolismq_question']);
		} else {
			$questionCounter = 0;
		}

		$answerCounter = 0;
		$formPosts = '';
		if (!empty($questionIds)) {
			foreach ($questionIds['dolismq_question'] as $questionId) {
				$controldettmp = $controldet;

				$answer = GETPOST('answer'.$questionId);
				if ($answer > 0) {
					$formPosts .= '&answer'.$questionId.'='.$answer;
				}

				//sauvegarder commentaire
				$comment = GETPOST('comment'.$questionId);

				if (dol_strlen($comment) > 0) {
					$formPosts .= '&comment'.$questionId.'='.$answer;
				}

			}
		}

		// Always output when not jmobile nor js
		$page = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
		$title =  $langs->trans('ValidateControl');
		$action = 'confirm_setValidated';
		$formquestion = '';
		$selectedchoice = 'yes';
		$useajax = 1;
		$height = 250;
		$width = 500;
		$disableformtag = 0;

		global $langs, $conf;

		$more = '<!-- formconfirm before calling page='.dol_escape_htmltag($page).' -->';
		$formconfirm = '';
		$inputok = array();
		$inputko = array();

		// Clean parameters
		$newselectedchoice = empty($selectedchoice) ? 'no' : $selectedchoice;
		if ($conf->browser->layout == 'phone') {
			$width = '95%';
		}

		// Set height automatically if not defined
		if (empty($height)) {
			$height = 220;
			if (is_array($formquestion) && count($formquestion) > 2) {
				$height += ((count($formquestion) - 2) * 24);
			}
		}

		if (is_array($formquestion) && !empty($formquestion)) {
			// First add hidden fields and value
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					if ($input['type'] == 'hidden') {
						$more .= '<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
					}
				}
			}

			// Now add questions
			$moreonecolumn = '';
			$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing">'."\n";
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					$size = (!empty($input['size']) ? ' size="'.$input['size'].'"' : '');	// deprecated. Use morecss instead.
					$moreattr = (!empty($input['moreattr']) ? ' '.$input['moreattr'] : '');
					$morecss = (!empty($input['morecss']) ? ' '.$input['morecss'] : '');

					if ($input['type'] == 'text') {
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="text" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
					} elseif ($input['type'] == 'password')	{
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="password" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
					} elseif ($input['type'] == 'select') {
						if (empty($morecss)) {
							$morecss = 'minwidth100';
						}

						$show_empty = isset($input['select_show_empty']) ? $input['select_show_empty'] : 1;
						$key_in_label = isset($input['select_key_in_label']) ? $input['select_key_in_label'] : 0;
						$value_as_key = isset($input['select_value_as_key']) ? $input['select_value_as_key'] : 0;
						$translate = isset($input['select_translate']) ? $input['select_translate'] : 0;
						$maxlen = isset($input['select_maxlen']) ? $input['select_maxlen'] : 0;
						$disabled = isset($input['select_disabled']) ? $input['select_disabled'] : 0;
						$sort = isset($input['select_sort']) ? $input['select_sort'] : '';

						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
						if (!empty($input['label'])) {
							$more .= $input['label'].'</div><div class="tagtd left">';
						}
						$more .= $this->selectarray($input['name'], $input['values'], $input['default'], $show_empty, $key_in_label, $value_as_key, $moreattr, $translate, $maxlen, $disabled, $sort, $morecss);
						$more .= '</div></div>'."\n";
					} elseif ($input['type'] == 'checkbox') {
						$more .= '<div class="tagtr">';
						$more .= '<div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].' </div><div class="tagtd">';
						$more .= '<input type="checkbox" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$moreattr;
						if (!is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0') {
							$more .= ' checked';
						}
						if (is_bool($input['value']) && $input['value']) {
							$more .= ' checked';
						}
						if (isset($input['disabled'])) {
							$more .= ' disabled';
						}
						$more .= ' /></div>';
						$more .= '</div>'."\n";
					} elseif ($input['type'] == 'radio') {
						$i = 0;
						foreach ($input['values'] as $selkey => $selval) {
							$more .= '<div class="tagtr">';
							if ($i == 0) {
								$more .= '<div class="tagtd'.(empty($input['tdclass']) ? ' tdtop' : (' tdtop '.$input['tdclass'])).'">'.$input['label'].'</div>';
							} else {
								$more .= '<div clas="tagtd'.(empty($input['tdclass']) ? '' : (' "'.$input['tdclass'])).'">&nbsp;</div>';
							}
							$more .= '<div class="tagtd'.($i == 0 ? ' tdtop' : '').'"><input type="radio" class="flat'.$morecss.'" id="'.$input['name'].$selkey.'" name="'.$input['name'].'" value="'.$selkey.'"'.$moreattr;
							if ($input['disabled']) {
								$more .= ' disabled';
							}
							if (isset($input['default']) && $input['default'] === $selkey) {
								$more .= ' checked="checked"';
							}
							$more .= ' /> ';
							$more .= '<label for="'.$input['name'].$selkey.'">'.$selval.'</label>';
							$more .= '</div></div>'."\n";
							$i++;
						}
					} elseif ($input['type'] == 'date') {
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div>';
						$more .= '<div class="tagtd">';
						$more .= $this->selectDate($input['value'], $input['name'], 0, 0, 0, '', 1, 0);
						$more .= '</div></div>'."\n";
						$formquestion[] = array('name'=>$input['name'].'day');
						$formquestion[] = array('name'=>$input['name'].'month');
						$formquestion[] = array('name'=>$input['name'].'year');
						$formquestion[] = array('name'=>$input['name'].'hour');
						$formquestion[] = array('name'=>$input['name'].'min');
					} elseif ($input['type'] == 'other') {
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
						if (!empty($input['label'])) {
							$more .= $input['label'].'</div><div class="tagtd">';
						}
						$more .= $input['value'];
						$more .= '</div></div>'."\n";
					} elseif ($input['type'] == 'onecolumn') {
						$moreonecolumn .= '<div class="margintoponly">';
						$moreonecolumn .= $input['value'];
						$moreonecolumn .= '</div>'."\n";
					} elseif ($input['type'] == 'hidden') {
						// Do nothing more, already added by a previous loop
					} else {
						$more .= 'Error type '.$input['type'].' for the confirm box is not a supported type';
					}
				}
			}
			$more .= '</div>'."\n";
			$more .= $moreonecolumn;
		}

		// JQUI method dialog is broken with jmobile, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
		if (!empty($conf->dol_use_jmobile)) {
			$useajax = 0;
		}
		if (empty($conf->use_javascript_ajax)) {
			$useajax = 0;
		}

		if ($useajax) {
			$autoOpen = true;
			$dialogconfirm = 'dialog-confirm';
			$button = '';
			if (!is_numeric($useajax)) {
				$button = $useajax;
				$useajax = 1;
				$autoOpen = false;
				$dialogconfirm .= '-'.$button;
			}
			$pageyes = $page.(preg_match('/\?/', $page) ? '&' : '?').'action='.$action.'&confirm=yes';
			$pageno = ($useajax == 2 ? $page.(preg_match('/\?/', $page) ? '&' : '?').'confirm=no' : '');

			// Add input fields into list of fields to read during submit (inputok and inputko)
			if (is_array($formquestion)) {
				foreach ($formquestion as $key => $input) {
					//print "xx ".$key." rr ".is_array($input)."<br>\n";
					// Add name of fields to propagate with the GET when submitting the form with button OK.
					if (is_array($input) && isset($input['name'])) {
						if (strpos($input['name'], ',') > 0) {
							$inputok = array_merge($inputok, explode(',', $input['name']));
						} else {
							array_push($inputok, $input['name']);
						}
					}
					// Add name of fields to propagate with the GET when submitting the form with button KO.
					if (isset($input['inputko']) && $input['inputko'] == 1) {
						array_push($inputko, $input['name']);
					}
				}
			}

			// Show JQuery confirm box.
			$formconfirm .= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<div class="confirmtext">'.$formquestion['text'].'</div>'."\n";
			}
			if (!empty($more)) {
				$formconfirm .= '<div class="confirmquestions">'.$more.'</div>'."\n";
			}
			$answerCounter = $_COOKIE['answerCounter'];

			$questionForConfirm =  $langs->trans('YouAnswered') . ' ' . $answerCounter . ' ' . $langs->trans('question(s)')  . ' ' . $langs->trans('On') . ' ' . $questionCounter . '.';
			if ($questionCounter - $answerCounter != 0) {
				$questionForConfirm .= '<br><b>' . $langs->trans('BewareQuestionsAnswered', $questionCounter - $answerCounter) . '</b>';
			}

			$formconfirm .= ($questionForConfirm ? '<div class="confirmmessage">'.img_help('', '').' '.$questionForConfirm.'</div>' : '');
			$formconfirm .= '<div>'."\n";
			$formconfirm .= '<br><b>' . $langs->trans('ConfirmValidateControl') . '<b>';
			$formconfirm .= '</div>'."\n";
			$formconfirm .= '</div>'."\n";

			$formconfirm .= "\n<!-- begin ajax formconfirm page=".$page." -->\n";
			$formconfirm .= '<script type="text/javascript">'."\n";

			$formconfirm .= 'jQuery(document).ready(function() {
            $(function() {


            	$( "#'.$dialogconfirm.'" ).dialog(
            	{
                    autoOpen: '.($autoOpen ? 'true' : 'false').',';
			if ($newselectedchoice == 'no') {
				$formconfirm .= '
						open: function() {
            				$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
			}

			$formconfirm .= '
                    resizable: false,
                    height: "'.$height.'",
                    width: "'.$width.'",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities('Yes')).'": function() {
                        	var options = "&token='.urlencode(newToken()).'";
                        	var inputok = '.json_encode($inputok).';	/* List of fields into form */
                         	var pageyes = "'.dol_escape_js(!empty($pageyes) ? $pageyes : '').'";
                         	if (inputok.length>0) {
                         		$.each(inputok, function(i, inputname) {
                         			var more = "";
									var inputvalue;
                         			if ($("input[name=\'" + inputname + "\']").attr("type") == "radio") {
										inputvalue = $("input[name=\'" + inputname + "\']:checked").val();
									} else {
                         		    	if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         				inputvalue = $("#" + inputname + more).val();
									}
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
									console.log("check inputname="+inputname+" inputvalue="+inputvalue);
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         			options += "&kaka=oui"
                         		});
                         	}
                         	var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options + "'.dol_escape_js($formPosts).'";
            				if (pageyes.length > 0) { location.href = urljump; }
            				return;
                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities('No')).'": function() {
                        	var options = "&token='.urlencode(newToken()).'";
                         	var inputko = '.json_encode($inputko).';	/* List of fields into form */
                         	var pageno="'.dol_escape_js(!empty($pageno) ? $pageno : '').'";
                         	if (inputko.length>0) {
                         		$.each(inputko, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

            	var button = "'.$button.'";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#'.$dialogconfirm.'").dialog("open");
        			});
                }
            });
            });
            </script>';
			$formconfirm .= "<!-- end ajax formconfirm -->\n";
		} else {
			$formconfirm .= "\n<!-- begin formconfirm page=".dol_escape_htmltag($page)." -->\n";

			if (empty($disableformtag)) {
				$formconfirm .= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
			}

			$formconfirm .= '<input type="hidden" name="action" value="'.$action.'">'."\n";
			$formconfirm .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";

			$formconfirm .= '<table class="valid centpercent">'."\n";

			// Line title
			$formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="2">';
			$formconfirm .= img_picto('', 'recent').' '.$title;
			$formconfirm .= '</td></tr>'."\n";

			// Line text
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">'.$formquestion['text'].'</td></tr>'."\n";
			}

			// Line form fields
			if ($more) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">'."\n";
				$formconfirm .= $more;
				$formconfirm .= '</td></tr>'."\n";
			}

			// Line with question
			$formconfirm .= '<tr class="valid">';
			$formconfirm .= '<td class="valid">'.$questionForConfirm.'</td>';
			$formconfirm .= '<td class="valid center">';
			$formconfirm .= $form->selectyesno('confirm', $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly');
			$formconfirm .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="'.$langs->trans('Validate').'">';
			$formconfirm .= '</td>';
			$formconfirm .= '</tr>'."\n";

			$formconfirm .= '</table>'."\n";

			if (empty($disableformtag)) {
				$formconfirm .= "</form>\n";
			}
			$formconfirm .= '<br>';

			if (empty($conf->use_javascript_ajax)) {
				$formconfirm .= '<!-- code to disable button to avoid double clic -->';
				$formconfirm .= '<script type="text/javascript">'."\n";
				$formconfirm .= '
				$(document).ready(function () {
					$(".confirmvalidatebutton").on("click", function() {
						console.log("We click on button");
						$(this).attr("disabled", "disabled");
						setTimeout(\'$(".confirmvalidatebutton").removeAttr("disabled")\', 3000);
						//console.log($(this).closest("form"));
						$(this).closest("form").submit();
					});
				});
				';
				$formconfirm .= '</script>'."\n";
			}

			$formconfirm .= "<!-- end formconfirm -->\n";
		}

	}

	// SetReopened confirmation
	if (($action == 'setReopened' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ReOpenControl'), $langs->trans('ConfirmReOpenControl', $object->ref), 'confirm_setReopened', '', 'yes', 'actionButtonReOpen', 350, 600);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('LockControl'), $langs->trans('ConfirmLockControl', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' .dol_buildpath('/dolismq/view/control/control_list.php', 1) . '">' . $langs->trans('BackToList') . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Project
	if (!empty($conf->projet->enabled)) {
		$langs->load('projects');
		$morehtmlref .= $langs->trans('Project') . ' ';
		if (!empty($object->fk_project)) {
			$project->fetch($object->fk_project);
			$morehtmlref .= ': ' . $project->getNomUrl(1, '', 1);
		} else {
			$morehtmlref .= '';
		}
	}
	$morehtmlref .= '</div>';

	$object->picto = 'control_small@dolismq';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	unset($object->fields['fk_project']); // Hide field already shown in banner

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td><td>';
		print $form->showCategories($object->id, 'control', 1);
		print '</td></tr>';
	}

	$object->fetchObjectLinked('', 'product', '', 'dolismq_control');
	if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT) && (!empty($object->linkedObjectsIds['product']))) {
		//FKProduct -- Produit
		print '<tr><td class="titlefield">';
		print $langs->trans('Product');
		print '</td>';
		print '<td>';
		$product->fetch(array_shift($object->linkedObjectsIds['product']));
		if ($product > 0) {
			print $product->getNomUrl(1);
		}
		print '<td></tr>';
	}

	$object->fetchObjectLinked('', 'productbatch','', 'dolismq_control');
	if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT) && (!empty($object->linkedObjectsIds['productbatch']))) {
		//FKLot -- Numéro de série
		print '<tr><td class="titlefield">';
		print $langs->trans('Batch');
		print '</td>';
		print '<td>';
		$productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
		if ($productlot > 0) {
			print $productlot->getNomUrl(1);
		}
		print '</td></tr>';
	}

	$object->fetchObjectLinked('', 'societe','', 'dolismq_control');
	if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY) && (!empty($object->linkedObjectsIds['societe']))) {
		//Fk_soc - Tiers lié
		print '<tr><td class="titlefield">';
		print $langs->trans('ThirdParty');
		print '</td>';
		print '<td>';
		$thirdparty->fetch(array_shift($object->linkedObjectsIds['societe']));
		if ($thirdparty > 0) {
			print $thirdparty->getNomUrl(1);
		}
		print '</td></tr>';
	}

	$object->fetchObjectLinked('', 'project','', 'dolismq_control');
	if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PROJECT) && (!empty($object->linkedObjectsIds['project']))) {
		//Fk_project - Projet lié
		print '<tr><td class="titlefield">';
		print $langs->trans('Project');
		print '</td>';
		print '<td>';
		$project->fetch(array_shift($object->linkedObjectsIds['project']));
		if ($project > 0) {
			print $project->getNomUrl(1, '', 1);
		}
		print '</td></tr>';
	}

	$object->fetchObjectLinked('', 'project_task','', 'dolismq_control');
	if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_TASK) && (!empty($object->linkedObjectsIds['project_task']))) {
		//Fk_task - Tâche liée
		print '<tr><td class="titlefield">';
		print $langs->trans('Task');
		print '</td>';
		print '<td>';
		$task->fetch(array_shift($object->linkedObjectsIds['project_task']));
		if ($task > 0) {
			print $task->getNomUrl(1);
		}
		print '</td></tr>';
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	if ($permissiontoadd > 0 && $object->status < 1) {
		$user->rights->control = new stdClass();
		$user->rights->control->write = 1;
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php'; ?>

	<script type="text/javascript">
		$(function () {
			let answerCounter = 0
			jQuery("#tablelines").children().each(function() {
				if ($(this).find(".answer.active").length > 0) {
					answerCounter += 1;
				}
			})

			jQuery('.answerCounter').text(answerCounter)
		})
	</script>
	<?php

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?action=save&id='.$object->id.'" id="saveControl" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="save">';

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Modify
//			if ($object->status == $object::STATUS_DRAFT) {
//				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '">' . $langs->trans('Modify') . '</a>';
//			} else {
//				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeDraft')) . '">' . $langs->trans('Modify') . '</span>';
//			}

			// Save question answer
			if ($object->status == $object::STATUS_DRAFT) {
				print '<input type="submit" id="saveButton" class="saveButton butActionRefused" value="' . $langs->trans('Save') . '">';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeDraft')) . '">' . $langs->trans('Save') . '</span>';
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				print '<a class="validateButton butAction" id="validateButton" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setValidated' . '">' . $langs->trans('Validate') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeDraft')) . '">' . $langs->trans('Validate') . '</span>';
			}

			// Set verdict control
			if ($object->status == $object::STATUS_VALIDATED && $object->verdict == null) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setVerdict' . '">' . $langs->trans('SetOK/KO') . '</a>';
				}
			} elseif ($object->status == $object::STATUS_DRAFT) {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidatedToSetVerdict')) . '">' . $langs->trans('SetOK/KO') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlVerdictSelected'))  . '">' . $langs->trans('SetOK/KO') . '</span>';
			}

			// ReOpen
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonReOpen" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setDraft' . '">' . $langs->trans('ReOpened') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidated')) . '">' . $langs->trans('ReOpened') . '</span>';
			}

			// Lock
			if ($object->status == $object::STATUS_VALIDATED && $object->verdict != null) {
				print '<span class="butAction" id="actionButtonLock">' . $langs->trans('Lock') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidatedToLock')) . '">' . $langs->trans('Lock') . '</span>';
			}

			// Send email
			if ($object->status == $object::STATUS_LOCKED) {
				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle', '', $object->status == $object::STATUS_LOCKED);
			} else {
				print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans('ControlMustBeLockedToSendEmail')) . '">' . $langs->trans('SendMail') . '</span>';
			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>';
	}

	// QUESTION LINES
	print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';

	$object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');
	$questionIds = $object->linkedObjectsIds;

	if (!empty($questionIds)) {
		$questionCounter = count($questionIds['dolismq_question']);
	} else {
		$questionCounter = 0;
	}

	print $langs->trans('YouAnswered') . ' ' . '<span class="answerCounter"></span>' . ' ' . $langs->trans('question(s)') . ' ' . $langs->trans('On') . ' ' . $questionCounter;

	print load_fiche_titre($langs->trans('LinkedQuestionsList'), '', ''); ?>

		<!-- Réponses -->
<!--	<div class="control-audit multiselect">-->
<!--		<div class="wpeo-table table-flex">-->
<!--			<div class="table-row">-->
<!--				<div class="table-cell table-250 table-end --><?php //echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?><!--">-->
<!--					--><?php
//					print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 1 ? 'active' : '') . '" id="select_all_answer" value="1">';
//					print '<i class="fas fa-check"></i>';
//					print '</span>';
//
//					print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 2 ? 'active' : '') . '" id="select_all_answer" value="2">';
//					print '<i class="fas fa-times"></i>';
//					print '</span>';
//
//					print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 3 ? 'active' : '') . '" id="select_all_answer" value="3">';
//					print '<i class="fas fa-tools"></i>';
//					print '</span>';
//
//					print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 4 ? 'active' : '') . '" id="select_all_answer" value="4">';
//					print 'N/A';
//					print '</span>';
//					?>
<!--				</div>-->
<!--			</div>-->
<!--		</div>-->
<!--	</div>-->

	<?php print '<div id="tablelines" class="control-audit noborder noshadow" width="100%">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

	// Define colspan for the button 'Add'
	$colspan = 3;

	// Lines
	if ( ! empty($questionIds['dolismq_question']) && $questionIds > 0) {
		foreach ($questionIds['dolismq_question'] as $questionId) {
			$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
			$answer = 0;
			$comment = '';
			if ($result > 0 && is_array($result)) {
				$itemControlDet = array_shift($result);
				$answer = $itemControlDet->answer;
				$comment = $itemControlDet->comment;
			}
			$answer = GETPOST('answer'.$questionId) ?: $answer;
			$item = $question;
			$item->fetch($questionId);
			?>
			<div class="wpeo-table table-flex table-3 table-id-<?php echo $item->id ?>">
				<div class="table-row">
					<!-- Contenu et commentaire -->
					<div class="table-cell table-full">
						<div class="label"><strong><?php print $item->ref . ' - ' . $item->label; ?></strong></div>
						<div class="description"><?php print $item->description; ?></div>
						<div class="question-comment-container">
							<div class="question-ref">
								<?php
								if ( ! empty( $itemControlDet->ref ) ) {
									print '<span class="question-ref-title">' . $itemControlDet->ref . '</span> - ';
								}
								?>
								<?php if ($item->enter_comment > 0) : ?>
									<?php print $langs->trans('Comment') . ' : '; ?>
								<?php endif; ?>
							</div>
							<?php if ($item->enter_comment > 0) : ?>
								<?php if ($object->status > 0 ) : ?>
									<?php print $comment; ?>
								<?php else : ?>
									<?php print '<input class="question-comment" name="comment'. $item->id .'" id="comment'. $item->id .'" value="'. $comment .'" '. ($object->status == 2 ? 'disabled' : '').'>'; ?>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
					<!-- Photo OK KO -->
					<?php if ($item->show_photo > 0) : ?>
						<div class="table-cell table-450 cell-photo-check">
						<?php
						if (!empty($conf->global->DOLISMQ_CONTROL_DISPLAY_MEDIAS)) :
							if (dol_strlen($item->photo_ok)) {
								$urladvanced = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ok/' . $item->photo_ok, 0, 'entity=' . $conf->entity);
								print ($urladvanced) ? '<a href="' . $urladvanced . '" class="question-photo-check ok">' : '<div class="question-photo-check ok">';
								print '<img class="photo photo-ok' . ($urladvanced ? ' clicked-photo-preview' : '') . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ok/thumbs/' . preg_replace('/\./', '_mini.', $item->photo_ok)) . '" >';
								print '<i class="fas fa-check-circle"></i>';
								print ($urladvanced) ? '</a>' : '</div>';
							} else {
								print '<div class="question-photo-check ok">';
								print '<img class="photo photo-ok" height="80" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
								print '<i class="fas fa-check-circle"></i>';
								print '</div>';
							}
							if (dol_strlen($item->photo_ko)) {
								$urladvanced               = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ko/' . $item->photo_ko, 0, 'entity=' . $conf->entity);
								print ($urladvanced) ? '<a href="' . $urladvanced . '" class="question-photo-check ko">' : '<div class="question-photo-check ko">';
								print '<img class="photo photo-ko'. ($urladvanced ? ' clicked-photo-preview' : '').'" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ko/thumbs/' . preg_replace('/\./', '_mini.', $item->photo_ko)) . '" >';
								print '<i class="fas fa-times-circle"></i>';
								print ($urladvanced) ? '</a>' : '</div>';
							} else {
								print '<div class="question-photo-check ko">';
								print '<img class="photo photo-ko question-photo-check ko" height="80" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
								print '<i class="fas fa-times-circle"></i>';
								print '</div>';
							}
						endif;
						?>
					</div>
					<?php endif; ?>
				</div>
				<div class="table-row">
					<!-- Galerie -->
					<?php if ($item->authorize_answer_photo > 0) : ?>
						<div class="table-cell table-full linked-medias answer_photo">
						<?php if ($object->status > 0 ) : ?>
							<?php $relativepath = 'dolismq/medias/thumbs';
							print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/control/'. $object->ref . '/answer_photo/' . $item->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'control/'. $object->ref . '/answer_photo/' . $item->ref, null, (GETPOST('favorite_answer_photo') ? GETPOST('favorite_answer_photo') : $itemControlDet->answer_photo ), 0, 0, 1);
							print '</td></tr>'; ?>
						<?php else : ?>
							<?php print '<input style="display: none" class="fast-upload" type="file" id="fast-upload-answer-photo'.$item->id.'" name="userfile'.$item->id.'[]" nonce="answer_photo'.$item->id.'" multiple capture="environment" accept="image/*" onchange="window.eoxiaJS.mediaGallery.fastUpload(this.nonce)">'; ?>
							<label for="fast-upload-answer-photo<?php echo $item->id ?>">
								<div class="wpeo-button button-square-50">
									<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
								</div>
							</label>
							<input type="hidden" class="question-answer-photo" id="answer_photo<?php echo $item->id ?>" name="answer_photo<?php echo $item->id ?>" value="test"/>
							<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="<?php echo $item->id ?>">
								<input type="hidden" class="type-from" value="answer_photo"/>
								<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
							</div>
							<?php $relativepath = 'dolismq/medias/thumbs';
							print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/control/'. $object->ref . '/answer_photo/' . $item->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'control/'. $object->ref . '/answer_photo/' . $item->ref, null, (GETPOST('favorite_answer_photo') ? GETPOST('favorite_answer_photo') : $itemControlDet->answer_photo ), 0, 1, 1); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<!-- Réponses -->
					<div class="table-cell table-250 <?php echo ($item->authorize_answer_photo == 0) ? 'table-end' : '' ?> <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
						<?php
						print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';
						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 1 ? 'active' : '') . '" value="1">';
						print '<i class="fas fa-check"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 2 ? 'active' : '') . '" value="2">';
						print '<i class="fas fa-times"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 3 ? 'active' : '') . '" value="3">';
						print '<i class="fas fa-tools"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($answer == 4 ? 'active' : '') . '" value="4">';
						print 'N/A';
						print '</span>';
						?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	include DOL_DOCUMENT_ROOT . '/custom/dolismq/core/tpl/dolismq_medias_gallery_modal.tpl.php';

	print '</div>';
	print '</div>';
	print '</form>';
	print dol_get_fiche_end();

	$includedocgeneration = 1;
	if ($includedocgeneration) {
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

		$objref = dol_sanitizeFileName($object->ref);
		$dir_files = $object->element . 'document/' . $objref;
		$filedir = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER['PHP_SELF'] . '?id=' . $id;

		$defaultmodel = 'controldocument_odt';
		$title = $langs->trans('WorkUnitDocument');

		print dolismqshowdocuments('dolismq:ControlDocument', $dir_files, $filedir, $urlsource, 1, 1, $object->model_pdf, 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object, 0, 'remove_file', (($object->status >= 0) ? 1 : 0));
		print '</div>';

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', dol_buildpath('/dolismq/view/control/control_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'dolismq';
	$defaulttopic = 'InformationMessage';
	$objref = dol_sanitizeFileName($object->ref);
	$dir_files = $object->element . 'document/' . $objref;
	$diroutput = $upload_dir . '/' . $dir_files;
	$trackid = 'dolismq'.$object->id;

//	$filter                 = array('t.src_object_id' => $object->id );
//	$ecmfile->fetch(0, '', '', '', '', 'dolismq_control', $object->id);
//	//echo '<pre>'; print_r($ecmfile); echo '</pre>'; exit;
////	if ( ! empty($controldocument) && is_array($controldocument)) {
////		$controldocument = array_shift($controldocument);
////		$ref                    = dol_sanitizeFileName($controldocument->ref);
////	}
//
//	$ref = dol_sanitizeFileName($ecmfile->filename);

	if ($action == 'presend') {
		$langs->load('mails');

		$titreform = 'SendMail';

		$object->fetch_projet();

		if ( ! in_array($object->element, array('societe', 'user', 'member'))) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			$fileparams = dol_dir_list($diroutput, 'files', 0, '');
			foreach ($fileparams as $fileparam) {
				preg_match('/' . $object->ref . '/', $fileparam['name']) ? $filevalue[] = $fileparam['fullname'] : 0;
			}
		}

		// Define output language
		$outputlangs = $langs;
		$newlang     = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
			$newlang = $_REQUEST['lang_id'];
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}

		if ( ! empty($newlang)) {
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			// Load traductions files required by page
			$outputlangs->loadLangs(array('dolismq'));
		}

		$topicmail = '';
		if (empty($object->ref_client)) {
			$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
		} elseif ( ! empty($object->ref_client)) {
			$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
		}

		print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans($titreform));

		print dol_get_fiche_head('');

		// Create form for email
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail                       = new FormMail($db);
		$formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
		$formmail->fromtype             = (GETPOST('fromtype') ? GETPOST('fromtype') : ( ! empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));
		$formmail->fromid               = $user->id;
		$formmail->trackid              = $trackid;
		$formmail->fromname             = $user->firstname . ' ' . $user->lastname;
		$formmail->frommail             = $user->email;
		$formmail->fromalsorobot        = 1;
		$formmail->withfrom             = 1;

		// Fill list of recipient with email inside <>.
		$liste = array();
		if ( ! empty($object->socid) && $object->socid > 0 && ! is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if (is_object($object->thirdparty)) {
			foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}

		if ( ! empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
			$listeuser = array();
			$fuserdest = new User($db);

			$result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql' => 't.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
			if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
				foreach ($fuserdest->users as $uuserdest) {
					$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
				}
			} elseif ($result < 0) {
				setEventMessages(null, $fuserdest->errors, 'errors');
			}
			if (count($listeuser) > 0) {
				$formmail->withtouser   = $listeuser;
				$formmail->withtoccuser = $listeuser;
			}
		}

		$formmail->withto              = 1;
		$formmail->withtofree          = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1') : '1');
		$formmail->withtocc            = $liste;
		$formmail->withtoccc           = $conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic           = $topicmail;
		$formmail->withfile            = 2;
		$formmail->withbody            = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel          = 1;

		//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
		if ( ! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude = null;

		// Make substitution in email content
		$substitutionarray                       = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
		$substitutionarray['__CHECK_READ__']     = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
		$substitutionarray['__CONTACTCIVNAME__'] = '';
		$substitutionarray['__REF__']            = $ref;
		$parameters                              = array(
			'mode' => 'formemail'
		);
		complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

		// Find the good contact address
		$tmpobject = $object;

		$contactarr = array();
		$contactarr = $tmpobject->liste_contact(-1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			$contactstatic = new Contact($db);

			foreach ($contactarr as $contact) {
				$contactstatic->fetch($contact['id']);
				$substitutionarray['__CONTACT_NAME_' . $contact['code'] . '__'] = $contactstatic->getFullName($outputlangs, 1);
			}
		}

		// Array of substitutions
		$formmail->substit = $substitutionarray;

		// Array of other parameters
		$formmail->param['action']    = 'send';
		$formmail->param['models']    = $modelmail;
		$formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
		$formmail->param['id']        = $object->id;
		$formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
		$formmail->param['fileinit']  = $filevalue;

		// Show form
		print $formmail->get_form();

		print dol_get_fiche_end();
	}
}

// End of page
llxFooter();
$db->close();
