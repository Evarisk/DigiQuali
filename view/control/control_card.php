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
 *   	\file       control_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view control
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
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

require_once __DIR__.'/../../class/control.class.php';
require_once __DIR__.'/../../class/sheet.class.php';
require_once __DIR__.'/../../class/question.class.php';
require_once __DIR__.'/../../lib/dolismq_control.lib.php';
require_once __DIR__.'/../../core/modules/dolismq/control/mod_control_standard.php';
require_once __DIR__ . '/../../lib/dolismq_function.lib.php';

global $langs, $conf, $user, $db;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'controlcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object        = new Control($db);
$controldet    = new ControlLine($db);
$sheet         = new Sheet($db);
$question      = new Question($db);
$usertmp       = new User($db);
$product       = new Product($db);
$project       = new Project($db);
$productlot    = new Productlot($db);
$extrafields   = new ExtraFields($db);
$refControlMod = new $conf->global->DOLISMQ_CONTROL_ADDON($db);

$diroutputmassaction = $conf->dolismq->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('controlcard', 'globalcard')); // Note that conf->hooks_modules contains array

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


$permissiontoread = $user->rights->dolismq->control->read;
$permissiontoadd = $user->rights->dolismq->control->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->control->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->dolismq->control->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->dolismq->control->write; // Used by the include of actions_dellink.inc.php
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

	$backurlforlist = dol_buildpath('/dolismq/view/control/control_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/control/control_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'addQuestion') {
		$questionId = GETPOST('questionId');
		$question->fetch($questionId);
		$question->add_object_linked($object->element,$id);


		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}
	$triggermodname = 'DOLISMQ_AUDIT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ($action == 'save') {

		$controldet = new ControlLine($db);
		$sheet->fetch($object->fk_sheet);
		$object->fetchQuestionsLinked($sheet->id, 'sheet');
		$questionIds = $object->linkedObjectsIds;
		foreach ($questionIds['question'] as $questionId) {
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
		setEventMessages($langs->trans('AnswerSaved') . ' ' . $question->ref, array());
		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc

	if ($action == 'builddoc' && $permissiontoadd) {
		if (is_numeric(GETPOST('model', 'alpha'))) {
			$error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Model"));
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
				$outputlangs = new Translate("", $conf);
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
					setEventMessages($langs->trans("FileGenerated"), null);

					$urltoredirect = $_SERVER['REQUEST_URI'];
					$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
					$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

					header('Location: '.$urltoredirect.'#builddoc');
					exit;
				}
			}
		}
	}

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}

	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'confirm_setVerdict' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		$object->fetch($id);
		if ( ! $error) {
			$object->verdict = GETPOST('verdict', 'int');
			$result = $object->update($user);
			if ($result > 0) {
				// Set verdict Control
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set verdict Control error
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
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
				$object->fetchQuestionsLinked($sheet->id, 'sheet');
				$questionIds = $object->linkedObjectsIds;
				foreach ($questionIds['question'] as $questionId) {
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
				header("Location: " . $urltogo);
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
				header("Location: " . $urltogo);
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
	$trackid = 'control'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title         = $langs->trans("Control");
$title_create  = $langs->trans("NewControl");
$title_edit    = $langs->trans("ModifyControl");

$help_url = '';
$morejs   = array("/dolismq/js/dolismq.js.php");
$morecss  = array("/dolismq/css/dolismq.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', "dolismq@dolismq");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate control-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired titlefieldcreate">' . $langs->trans("Ref") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="' . $refControlMod->getNextValue($object) . '">';
	print $refControlMod->getNextValue($object);
	print '</td></tr>';

	//FK User controller
	if ($conf->global->DOLISQM_USER_CONTROLLER < 0 || empty($conf->global->DOLISQM_USER_CONTROLLER)) {
		$userlist = $form->select_dolusers(( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
		print '<tr>';
		print '<td class="fieldrequired " style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('fk_user_controller', $userlist, ( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	} else {
		$usertmp->fetch($conf->global->DOLISQM_USER_CONTROLLER);
		print '<tr>';
		print '<td class="fieldrequired " style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>' . $usertmp->getNomUrl(1) . '</td>';
		print '<input type="hidden" name="fk_user_controller" value="' . $conf->global->DOLISQM_USER_CONTROLLER . '">';
		print '</td></tr>';
	}

	//FK Product
	print '<tr><td class="fieldrequired ">' . img_picto('', 'product') . ' ' . $langs->trans("Product") . ' ' . $langs->trans('Or') . ' '. $langs->trans('Service') . '</td><td>';
	$events    = array();
	$events[1] = array('method' => 'getProductLots', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/lots.php?showempty=1', 1), 'htmlname' => 'fk_lot');
	print $form->select_produits(GETPOST('fk_product'), 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProductsOrServices' , 0, 'minwidth300');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//FK LOT
	print '<tr><td class="">';
	print img_picto('', 'lot') . ' ' . $langs->trans("Lot");
	print '</td><td class="lot-container">';
	print '<span class="lot-content">';
	$data = json_decode(file_get_contents('php://input'), true);
	dol_strlen($data['productRef']) > 0 ? $product->fetch(0, $data['productRef']) : 0;

	print dolismq_select_product_lots(( ! empty(GETPOST('fk_product')) ? GETPOST('fk_product') : $product->id), GETPOST('fk_lot'), 'fk_lot', 1, '', '', 0, 'minwidth300', false, 0, array(), false, '', 'fk_lot');
	print '</span>';
	print '</td></tr>';

	//FK SHEET
	print '<tr><td class="fieldrequired">' . $langs->trans("SheetLinked") . '</td><td>';
	print $sheet->select_sheet_list();
	print '</td></tr>';

	//FK Project
	print '<tr><td class="">' . $langs->trans("ProjectLinked") . '</td><td>';
	print $formproject->select_projects('',  GETPOST('fk_project'), 'fk_project', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'minwidth300');
	print '</td></tr>';

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

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = controlPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Control"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteControl'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formcontrol = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formcontrol, 'yes', 1);
	}

	if ($action == 'setVerdict') {
		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'select', 'name' => 'verdict', 'label' => '<span class="fieldrequired">' . $langs->trans("VerdictControl") . '</span>', 'values' => array('1' => 'OK', '2' => 'KO'), 'select_show_empty' => 0),
		);

		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('SetOK/KO'), $text, 'confirm_setVerdict', $formquestion, '', 1, 250);
	}

	// SetValidated confirmation
	if (($action == 'setValidated')) {
		// a mettre après le confirm
		$controldet = new ControlLine($db);
		$sheet->fetch($object->fk_sheet);
		$object->fetchQuestionsLinked($sheet->id, 'sheet');
		$questionIds = $object->linkedObjectsIds;
		$questionCounter = count($questionIds['question']);
		$answerCounter = 0;
		$formPosts = '';
		foreach ($questionIds['question'] as $questionId) {
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

		// Always output when not jmobile nor js
		$page = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
		$title =  $langs->trans('ValidateControl');
		$questionForConfirm =  $langs->trans('ConfirmValidateControl');
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
		$newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
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

			$formconfirm .= ($questionForConfirm ? '<div class="confirmmessage">'.img_help('', '').' '.$questionForConfirm.'</div>' : '');
			$formconfirm .= '<div>'."\n";
			$formconfirm .= '<b>' . $langs->trans('ThereAre') . ' ' . $questionCounter . ' ' . $langs->trans('question(s)') . '<b>' . "<br>";
			$formconfirm .= '<b>' . $langs->trans('YouAnswered') . ' ' . $answerCounter . ' ' . $langs->trans('question(s)') .'<b>' . "\n";
			$formconfirm .= '</div>'."\n";
			$formconfirm .= '</div>'."\n";

			$formconfirm .= "\n<!-- begin ajax formconfirm page=".$page." -->\n";
			$formconfirm .= '<script type="text/javascript">'."\n";

			$formconfirm .= 'jQuery(document).ready(function() {
            $(function() {


            	$( "#'.$dialogconfirm.'" ).dialog(
            	{
                    autoOpen: '.($autoOpen ? "true" : "false").',';
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
                        "'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
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
                        "'.dol_escape_js($langs->transnoentities("No")).'": function() {
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
			$formconfirm .= $form->selectyesno("confirm", $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly');
			$formconfirm .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="'.$langs->trans("Validate").'">';
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
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ReOpenControl'), $langs->trans('ConfirmReOpenControl', $object->ref), 'confirm_setReopened', '', 'yes', 'actionButtonReOpen', 350, 600);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockControl'), $langs->trans('ConfirmLockControl', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}


	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formcontrol = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formcontrol, 0, 1, 220);
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
	$linkback = '<a href="'.dol_buildpath('/dolismq/control_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	//FKUserController -- Contrôleur
	print '<tr><td class="titlefield">';
	print $langs->trans("FKUserController");
	print '</td>';
	print '<td>';
	$usertmp->fetch($object->fk_user_controller);
	if ($usertmp > 0) {
		print $usertmp->getNomUrl(1);
	}
	print '</td></tr>';

	//Address -- Adresse
	print '<tr><td class="titlefield">';
	print $langs->trans("Address");
	print '</td>';
	print '<td>';
	print $usertmp->address;
	print '</td></tr>';

	//Address -- Adresse
	print '<tr><td class="titlefield">';
	print $langs->trans("Login");
	print '</td>';
	print '<td>';
	print $usertmp->login;
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	//FKProduct -- Produit
	print '<tr><td class="titlefield">';
	print $langs->trans("Product");
	print '</td>';
	print '<td>';
	$product->fetch($object->fk_product);
	if ($product > 0) {
		print $product->getNomUrl(1);
	}
	print '<td>';

	// -- Contrôleur
	print '<tr><td class="titlefield">';
	print $langs->trans("FKSheet");
	print '</td>';
	print '<td>';
	$sheet->fetch($object->fk_sheet);
	if ($sheet > 0) {
		print $sheet->getNomUrl(1);
	}
	print '<td>';

	print '</td></tr>';

	//FKLot -- Numéro de série
	print '<tr><td class="titlefield">';
	print $langs->trans("Batch");
	print '</td>';
	print '<td>';
	$productlot->fetch($object->fk_lot);
	if ($productlot > 0) {
		print $productlot->getNomUrl(1);
	}
	print '</td></tr>';

	//Fk_project - Projet lié
	print '<tr><td class="titlefield">';
	print $langs->trans("Project");
	print '</td>';
	print '<td>';
	$project->fetch($object->fk_project);
	if ($project > 0) {
		print $project->getNomUrl(1);
	}
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
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
	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			print '<a class="' . (($object->status == 0) ? 'saveButton butAction' : 'butActionRefused classfortooltip') . '" href="'.$_SERVER["PHP_SELF"].'?action=save&id='.$object->id.'" id="' . (($object->status == 0) ? 'actionButtonSave' : '') . '" title="' . (($object->status == 0) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeDraft"))) . '">' . $langs->trans("Save") . '</a>';
			print '<a class="' . (($object->status == 0) ? 'validateButton butAction' : 'butActionRefused classfortooltip') . '" href="'.$_SERVER["PHP_SELF"].'?action=setValidated&id='.$object->id.'" id="' . (($object->status == 0) ? 'actionButtonValidate' : '') . '" title="' . (($object->status == 0) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeDraft"))) . '">' . $langs->trans("Validate") . '</a>';
			// Set verdict control
			if ($object->status == 1 && $object->verdict == null) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=setVerdict'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close').'"';
					print '>'.$langs->trans('SetOK/KO').'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" title="'.$langs->trans("ControlVerdictSelected").'"';
				print '>'.$langs->trans('SetOK/KO').'</a>';
			}
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1 ) ? 'actionButtonReOpen' : '') . '" title="' . (($object->status == 1 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeValidated"))) . '">' . $langs->trans("ReOpened") . '</span>';
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1) ? 'actionButtonLock' : '') . '" title="' . (($object->status == 1 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeValidatedToLock"))) . '">' . $langs->trans("Lock") . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}

	// QUESTION LINES
	print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
	$object->fetchQuestionsLinked($sheet->id, 'sheet');
	$questionIds = $object->linkedObjectsIds;
	print $langs->trans('YouAnswered') . ' ' . '<span class="answerCounter"></span>' . ' ' . $langs->trans('question(s)') . ' ' . $langs->trans('On') . ' ' . count($questionIds);

	print load_fiche_titre($langs->trans("LinkedQuestionsList"), '', '');
	print '<div id="tablelines" class="control-audit noborder noshadow" width="100%">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

// Define colspan for the button 'Add'
	$colspan = 3;

// Lines

//	print '<tr class="liste_titre">';
//	print '<td>' . $langs->trans('Ref') . '</td>';
//	print '<td>' . $langs->trans('Description') . '</td>';
//	print '<td>' . $langs->trans('PhotoOk') . '</td>';
//	print '<td>' . $langs->trans('PhotoKo') . '</td>';
//	print '<td class="center">' . $langs->trans('Answer') . '</td>';
//	print '<td>' . '</td>';
//	print '</tr>';

	if ( ! empty($questionIds['question']) && $questionIds > 0) {
		foreach ($questionIds['question'] as $questionId) {
			$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
			if ($result > 0 && is_array($result)) {
				$itemControlDet = array_shift($result);
				$answer = $itemControlDet->answer;
				$comment = $itemControlDet->comment;
			}
			$item = $question;
			$item->fetch($questionId);
			?>
			<div class="wpeo-table table-flex table-3">
				<div class="table-row">
					<div class="table-cell table-full">
						<strong><?php print $item->label; ?></strong><br>
						<?php print $item->description; ?>
					</div>
					<div class="table-cell table-175">
						<?php
						$urladvanced               = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ok/' . $item->photo_ok, 0, 'entity=' . $conf->entity);
						if ($urladvanced) print '<a href="' . $urladvanced . '">';
						print '<img width="60" class="photo photo-ok clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ok/thumbs/' . preg_replace('/\./', '_small.', $item->photo_ok)) . '" >';
						print '</a>';
						$urladvanced               = getAdvancedPreviewUrl('dolismq', $item->element . '/' . $item->ref . '/photo_ko/' . $item->photo_ko, 0, 'entity=' . $conf->entity);
						if ($urladvanced) print '<a href="' . $urladvanced . '">';
						print '<img width="60" class="photo photo-ko clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ko/thumbs/' . preg_replace('/\./', '_small.', $item->photo_ko)) . '" >';
						print '</a>';
						?>
					</div>
					<div class="table-cell table-225" <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>>
						<?php
						print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . (GETPOST('answer'.$questionId) == 1 ? 'active' : ($answer == 1 ? 'active' : '')) . '" value="1">';
						print '<i class="fas fa-check"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . (GETPOST('answer'.$questionId) == 2 ? 'active' : ($answer == 2 ? 'active' : '')) . '" value="2">';
						print '<i class="fas fa-times"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . (GETPOST('answer'.$questionId) == 3 ? 'active' : ($answer == 3 ? 'active' : '')) . '" value="3">';
						print '<i class="fas fa-tools"></i>';
						print '</span>';

						print '<span class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . (GETPOST('answer'.$questionId) == 4 ? 'active' : ($answer == 4 ? 'active' : '')) . '" value="4">';
						print 'N/A';
						print '</span>';
						?>
					</div>
				</div>
				<div class="table-row">
					<div class="table-cell table-full wpeo-gridlayout grid-4">
						<div class="question-comment-title"><?php print $langs->trans('Comment') . ' : '; ?></div>
						<?php if ($object->status > 0 ) : ?>
							<?php print $comment; ?>
						<?php else : ?>
							<div class="gridw-3"><?php print '<input class="question-comment" name="comment'. $item->id .'" id="comment'. $item->id .'" value="'. $comment .'" '. ($object->status == 2 ? 'disabled' : '').'>'; ?></div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	print dol_get_fiche_end();
	$includedocgeneration = 1;
	if ($includedocgeneration) {
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

		$objref    = dol_sanitizeFileName($object->ref);
		$dir_files = $object->element . 'document/' . $objref;
		$filedir   = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $id;

		$defaultmodel = 'controldocument_odt';
		$title        = $langs->trans('WorkUnitDocument');

		print dolismqshowdocuments('dolismq:ControlDocument', $dir_files, $filedir, $urlsource, 1, 1, $object->model_pdf, 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object, 0, 'remove_file', (($object->status == 2) ? 1 : 0));
	}
//
//	/*
//	 * Lines
//	 */
//
//	if (!empty($object->table_element_line))
//	{
//		// Show object lines
//		$result = $object->getLinesArray();
//
//		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
//		<input type="hidden" name="token" value="' . newToken().'">
//		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
//		<input type="hidden" name="mode" value="">
//		<input type="hidden" name="id" value="' . $object->id.'">
//		';
//
//		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
//			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
//		}
//
//		print '<div class="div-table-responsive-no-min">';
//		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
//		{
//			print '<table id="tablelines" class="noborder noshadow" width="100%">';
//		}
//
//		if (!empty($object->lines))
//		{
//			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
//		}
//
//		// Form to add new line
//		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
//		{
//			if ($action != 'editline')
//			{
//				// Add products/services form
//				$object->formAddObjectLine(1, $mysoc, $soc);
//
//				$parameters = array();
//				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
//			}
//		}
//
//		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
//		{
//			print '</table>';
//		}
//		print '</div>';
//
//		print "</form>\n";
//	}
}

// End of page
llxFooter();
$db->close();
