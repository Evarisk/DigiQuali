<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       question_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view question
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

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
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";

if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once '../../class/question.class.php';
require_once '../../core/modules/dolismq/question/mod_question_standard.php';
require_once '../../lib/dolismq_question.lib.php';
require_once '../../lib/dolismq_function.lib.php';

global $langs, $conf, $user, $db;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'questioncard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object         = new Question($db);
$extrafields    = new ExtraFields($db);
$ecmfile 		= new EcmFiles($db);
$refQuestionMod = new $conf->global->DOLISMQ_QUESTION_ADDON($db);

$diroutputmassaction = $conf->dolismq->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('questioncard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->dolismq->question->read;
$permissiontoadd = $user->rights->dolismq->question->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->question->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->dolismq->question->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->dolismq->question->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'dolismq', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = dol_buildpath('/dolismq/view/question/question_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/question/question_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}


	$triggermodname = 'DOLISMQ_AUDIT_MODIFY'; // Name of trigger action code to execute when we modify record

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

	if ($action == 'update' && !empty($permissiontoadd)) {
		$categories = GETPOST('categories', 'array');
		$object->setCategories($categories);
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ( ! $error && $action == "addFiles") {
		$data = json_decode(file_get_contents('php://input'), true);

		$filenames  = $data['filenames'];
		$questionId = $data['questionId'];
		$type 	    = $data['type'];
		$object->fetch($questionId);
		if (dol_strlen($object->ref) > 0) {
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $object->ref . '/' . $type;
		} else {
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/' . 'QU0/' . $type ;
		}
		$filenames = preg_split('/vVv/', $filenames);
		array_pop($filenames);

		if ( ! (empty($filenames))) {
			if ( ! is_dir($conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/')) {
				dol_mkdir($conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/');
			}

			if ( ! is_dir($conf->dolismq->multidir_output[$conf->entity] . '/question/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/QU0') )) {

				dol_mkdir($conf->dolismq->multidir_output[$conf->entity] . '/question/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/QU0'));
			}

			foreach ($filenames as $filename) {
				$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';

				if (is_file($conf->ecm->multidir_output[$conf->entity] . '/dolismq/medias/' . $filename)) {
					$pathToECMPhoto = $conf->ecm->multidir_output[$conf->entity] . '/dolismq/medias/' . $filename;

					if ( ! is_dir($pathToQuestionPhoto)) {
						mkdir($pathToQuestionPhoto);
					}

					copy($pathToECMPhoto, $pathToQuestionPhoto . '/' . $filename);
					$ecmfile->fetch(0,'','ecm/dolismq/medias/' . $filename);
					$date = dol_print_date(dol_now(),'dayxcard');
					$extension = preg_split('/\./', $filename);
					$newFilename = $conf->entity . '_' . $ecmfile->id . '_' . (dol_strlen($object->ref) > 0 ? $object->ref : $refQuestionMod->getNextValue($object)). '_' . $date . '.' . $extension[1];
					rename($pathToQuestionPhoto . '/' . $filename, $pathToQuestionPhoto . '/' . $newFilename);

					global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
					$destfull = $pathToQuestionPhoto . '/' . $newFilename;

					// Create thumbs
					$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
					// Create mini thumbs for image (Ratio is near 16/9)
					$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
				}
			}
		}
	}

	if ( ! $error && $action == "unlinkFile" && $permissiontodelete) {
		$data = json_decode(file_get_contents('php://input'), true);

		$filename = $data['filename'];
		$type     = $data['type'];
		$id     = $data['id'];

		if ($id > 0) {
			$object->fetch($id);
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $object->ref . '/' . $type;
		} else {
			$pathToQuestionPhoto = $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/' . $type;
		}

		$files = dol_dir_list($pathToQuestionPhoto);

		foreach ($files as $file) {
			if (is_file($file['fullname']) && $file['name'] == $filename) {

				unlink($file['fullname']);
				if ($object->$type == $filename) {
					$object->$type = '';
					$object->update($user);
				}
			}
		}
		$files = dol_dir_list($pathToQuestionPhoto . '/thumbs');
		foreach ($files as $file) {
			if (preg_match('/' . preg_split('/\./', $filename)[0] . '/', $file['name'])) {
				unlink($file['fullname']);
			}
		}
//		if ($riskassessment->photo == $filename) {
//			$riskassessment->photo = '';
//			$riskassessment->update($user, true);
//		}
		$urltogo = str_replace('__ID__', $id, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
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
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}


	// Actions to send emails
	$triggersendname = 'DOLISMQ_AUDIT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_AUDIT_TO';
	$trackid = 'question'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Question");
$help_url = '';
$morejs   = array("/dolismq/js/dolismq.js.php");
$morecss  = array("/dolismq/css/dolismq.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans('NewQuestion'), '', 'dolismq@dolismq');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="createQuestionForm" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="' . $refQuestionMod->getNextValue($object) . '">';
	print $refQuestionMod->getNextValue($object);
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.GETPOST('label').'">';
	print '</td></tr>';

	print '<tr><td class=""><label class="fieldrequired" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', '', '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr class="linked-medias photo_ok"><td class=""><label for="photo_ok">' . $langs->trans("PhotoOk") . '</label></td><td>'; ?>
	<?php print '<input style="display: none" class="fast-upload" type="file" id="fast-upload-photo-ok" name="userfile[]" multiple capture="environment" accept="image/*">'; ?>
	<label for="fast-upload-photo-ok">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ok" name="photo_ok" value="<?php echo GETPOST('favorite_photo_ok') ?>"/>
	<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="type-from" value="photo_ok"/>
		<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
	</div>
	<?php
	$relativepath = 'dolismq/medias/thumbs';
	print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/photo_ok', 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, 'question/tmp/QU0/photo_ok', null, GETPOST('favorite_photo_ok'));
	print '</td></tr>';

	print '<tr class="linked-medias photo_ko"><td class=""><label for="photo_ko">' . $langs->trans("PhotoKo") . '</label></td><td>'; ?>
	<?php print '<input style="display: none" class="fast-upload" type="file" id="fast-upload-photo-ko" name="userfile2[]" multiple capture="environment" accept="image/*">'; ?>
	<label for="fast-upload-photo-ko">
		<div class="wpeo-button button-square-50">
			<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</label>
	<input type="hidden" class="favorite-photo" id="photo_ko" name="photo_ko" value="<?php echo GETPOST('favorite_photo_ko') ?>"/>
	<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="type-from" value="photo_ko"/>
		<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
	</div>
	<?php
	print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/tmp/QU0/photo_ko', 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, 'question/tmp/QU0/photo_ko', null, GETPOST('favorite_photo_ko'));
	print '</td></tr>';

	if (!empty($conf->categorie->enabled)) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('question', '', 'parent', 64, 0, 1);
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
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

	//dol_set_focus('input[name="ref"]');
}
include DOL_DOCUMENT_ROOT . '/custom/dolismq/core/tpl/dolismq_medias_gallery_modal.tpl.php';

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("Question"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

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

	print '<tr class="linked-medias photo_ok"><td><label for="photo_ok">' . $langs->trans("PhotoOk") . '</label></td><td>'; ?>
	<input type="hidden" class="favorite-photo" id="photo_ok" name="photo_ok" value="<?php echo (dol_strlen($object->photo_ok) > 0 ? $object->photo_ok : GETPOST('favorite_photo_ok')) ?>"/>
	<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="type-from" value="photo_ok"/>
		<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
	</div>
	<?php
	$relativepath = 'dolismq/medias/thumbs';
	print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ok', 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, 'question/'. $object->ref . '/photo_ok', null, (GETPOST('favorite_photo_ok') ? GETPOST('favorite_photo_ok') : $object->photo_ok ));
	print '</td></tr>';

	print '<tr class="linked-medias photo_ko"><td><label for="photo_ko">' . $langs->trans("PhotoKo") . '</label></td><td>'; ?>
	<input type="hidden" class="favorite-photo" id="photo_ko" name="photo_ko" value="<?php echo (dol_strlen($object->photo_ko) > 0 ? $object->photo_ko : GETPOST('favorite_photo_ko')) ?>"/>
	<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
		<input type="hidden" class="type-from" value="photo_ko"/>
		<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
	</div>
	<?php
	print dolismq_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $object->ref . '/photo_ko', 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, 'question/'. $object->ref . '/photo_ko', null,(GETPOST('favorite_photo_ko') ? GETPOST('favorite_photo_ko') : $object->photo_ko ));
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

	$head = questionPrepareHead($object);
	print dol_get_fiche_head($head, 'questionCard', $langs->trans("Question"), -1, "dolismq@dolismq");

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteQuestion'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockQuestion'), $langs->trans('ConfirmLockQuestion', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="'.dol_buildpath('/dolismq/view/question/question_list.php', 1).'?restore_lastsearch_values=1'.'">'.$langs->trans("BackToList").'</a>';

	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	//Description -- Description
	print '<tr><td class="titlefield">';
	print $langs->trans("Description");
	print '</td>';
	print '<td>';
	print $object->description;
	print '</td></tr>';

	//Photo OK -- Photo OK
	print '<tr><td class="titlefield">';
	print $langs->trans("PhotoOk");
	print '</td>';
	print '<td>';
	if (dol_strlen($object->photo_ok)) {
		$urladvanced               = getAdvancedPreviewUrl('dolismq', $object->element . '/' . $object->ref . '/photo_ok/' . $object->photo_ok, 0, 'entity=' . $conf->entity);
		if ($urladvanced) print '<a href="' . $urladvanced . '">';
		print '<img width="40" class="photo photo-ok clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($object->element . '/' . $object->ref . '/photo_ok/thumbs/' . preg_replace('/\./', '_mini.', $object->photo_ok)) . '" >';
		print '</a>';
	} else {
		print '<img height="40" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</td></tr>';

	//Photo KO -- Photo KO
	print '<tr><td class="titlefield">';
	print $langs->trans("PhotoKo");
	print '</td>';
	print '<td>';
	if (dol_strlen($object->photo_ko)) {
		$urladvanced               = getAdvancedPreviewUrl('dolismq', $object->element . '/' . $object->ref . '/photo_ko/' . $object->photo_ko, 0, 'entity=' . $conf->entity);
		if ($urladvanced) print '<a href="' . $urladvanced . '">';
		print '<img width="40" class="photo photo-ko clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($object->element . '/' . $object->ref . '/photo_ko/thumbs/' . preg_replace('/\./', '_mini.', $object->photo_ko)) . '" >';
		print '</a>';
	}  else {
		print '<img height="40" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</td></tr>';

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

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines))
		{
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
		{
			if ($action != 'editline')
			{
				// Add products/services form
				$object->formAddObjectLine(1, $mysoc, $soc);

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
//			// Send
//			if (empty($user->socid)) {
//				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle');
//			}

//			// Back to draft
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1) ? 'actionButtonLock' : '') . '">' . $langs->trans("Lock") . '</span>';
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit', '', $permissiontoadd);
			}

//			// Validate
//			if ($object->status == $object::STATUS_DRAFT) {
//				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0))	{
//					print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes', '', $permissiontoadd);
//				} else {
//					$langs->load("errors");
//					//print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes', '', 0);
//					print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
//				}
//			}

			// Clone
//			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&object=scrumsprint', '', $permissiontoadd);

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($object->status != 2) {
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}


}

// End of page
llxFooter();
$db->close();
