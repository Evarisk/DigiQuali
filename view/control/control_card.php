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
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'confirm_closeas' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setStatusControl($user, GETPOST('status', 'int'));
			if ($result > 0) {
				// Set Status Control
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set Status Control error
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
	print '<tr><td class="fieldrequired minwidth400">' . $langs->trans("Ref") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="' . $refControlMod->getNextValue($object) . '">';
	print $refControlMod->getNextValue($object);
	print '</td></tr>';

	//FK User controller
	if ($conf->global->DOLISQM_USER_CONTROLLER < 0 || empty($conf->global->DOLISQM_USER_CONTROLLER)) {
		$userlist = $form->select_dolusers(( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('fk_user_controller', $userlist, ( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	} else {
		$usertmp->fetch($conf->global->DOLISQM_USER_CONTROLLER);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>' . $usertmp->getNomUrl(1) . '</td>';
		print '<input type="hidden" name="fk_user_controller" value="' . $conf->global->DOLISQM_USER_CONTROLLER . '">';
		print '</td></tr>';
	}

	//FK Product
	print '<tr><td class="fieldrequired minwidth400">' . img_picto('', 'product') . ' ' . $langs->trans("Product") . '</td><td>';
	$events    = array();
	$events[1] = array('method' => 'getProductLots', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/lots.php?showempty=1', 1), 'htmlname' => 'fk_lot');
	print $form->select_produits(GETPOST('fk_product'), 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProducts', 0, 'minwidth300');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//FK LOT
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('', 'lot') . ' ' . $langs->trans("Lot");
	print '</td><td>';
	print dolismq_select_product_lots((empty(GETPOST('fk_product', 'int')) ? 1 : GETPOST('fk_product', 'int')), GETPOST('fk_lot'), 'fk_lot', 1, '', '', 0, 'minwidth300', false, 0, array(), false, '', 'fk_lot');
	print '</td></tr>';

	//FK SHEET
	print '<tr><td class="fieldrequired minwidth400">' . $langs->trans("SheetLinked") . '</td><td>';
	print $sheet->select_sheet_list();
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

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($title_edit, '', "dolismq@dolismq");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit control-table">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	//FK User controller
	if ($conf->global->DOLISQM_USER_CONTROLLER < 0 || empty($conf->global->DOLISQM_USER_CONTROLLER)) {
		$userlist = $form->select_dolusers(( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('fk_user_controller', $userlist, ( ! empty(GETPOST('fk_user_controller')) ? GETPOST('fk_user_controller') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	} else {
		$usertmp->fetch($conf->global->DOLISQM_USER_CONTROLLER);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('FKUserController', 'FKUserController_id', '', $object, 0) . '</td>';
		print '<td>' . $usertmp->getNomUrl(1) . '</td>';
		print '<input type="hidden" name="fk_user_controller" value="' . $conf->global->DOLISQM_USER_CONTROLLER . '">';
		print '</td></tr>';
	}

	//FK Product
	print '<tr><td class="fieldrequired minwidth400">' . img_picto('', 'product') . ' ' . $langs->trans("Product") . '</td><td>';
	$events    = array();
	$events[1] = array('method' => 'getProductLots', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/lots.php?showempty=1', 1), 'htmlname' => 'fk_lot');
	print $form->select_produits($object->fk_product, 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProducts', 0, 'minwidth300');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//FK LOT
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('', 'lot') . ' ' . $langs->trans("Lot");
	print '</td><td>';
	print dolismq_select_product_lots((empty(GETPOST('fk_product', 'int')) ? $object->fk_product : GETPOST('fk_product', 'int')), $object->fk_lot, 'fk_lot', 1, '', '', 0, 'minwidth300', false, 0, array(), false, '', 'fk_lot');
	print '</td></tr>';

	//FK SHEET
	print '<tr><td class="fieldrequired minwidth400">' . $langs->trans("SheetLinked") . '</td><td>';
	print $sheet->select_sheet_list($object->fk_sheet);
	print '</td></tr>';

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

	if ($action == 'closeas') {
		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'select', 'name' => 'status', 'label' => '<span class="fieldrequired">' . $langs->trans("CloseAs") . '</span>', 'values' => array($object::STATUS_OK => $object->LibStatut($object::STATUS_OK), $object::STATUS_KO => $object->LibStatut($object::STATUS_KO))),
		);

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('SetOK/KO'), $text, 'confirm_closeas', $formquestion, '', 1, 250);
	}

	// SetValidated confirmation
	if (($action == 'setValidated' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateControl'), $langs->trans('ConfirmValidateControl', $object->ref), 'confirm_setValidated', '', 'yes', 'actionButtonValidate', 350, 600);
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

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner

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
	print $langs->trans("SerialNumber");
	print '</td>';
	print '<td>';
	$productlot->fetch($object->fk_lot);
	if ($productlot > 0) {
		print $productlot->getNomUrl(1);
	}
	print '</td></tr>';

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
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Close as accepted/refused
			if ($object->statut == 1) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=closeas'.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#close').'"';
					print '>'.$langs->trans('SetOK/KO').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'"';
					print '>'.$langs->trans('SetOK/KO').'</a>';
				}
			}
			print '<a class="' . (($object->status != 2) ? 'saveButton butAction' : 'butActionRefused classfortooltip') . '" href="'.$_SERVER["PHP_SELF"].'?action=save&id='.$object->id.'" id="' . (($object->status == 0) ? 'actionButtonSave' : '') . '" title="' . (($object->status == 0 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeDraft"))) . '">' . $langs->trans("Save") . '</a>';
			print '<span class="' . (($object->status == 0) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 0) ? 'actionButtonValidate' : '') . '" title="' . (($object->status == 0 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeDraft"))) . '">' . $langs->trans("Validate") . '</span>';
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1 ) ? 'actionButtonReOpen' : '') . '" title="' . (($object->status == 1 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeValidated"))) . '">' . $langs->trans("ReOpened") . '</span>';
			print '<span class="' . (($object->status == 1) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1) ? 'actionButtonLock' : '') . '" title="' . (($object->status == 1 ) ? '' : dol_escape_htmltag($langs->trans("ControlMustBeValidatedToLock"))) . '">' . $langs->trans("Lock") . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}

	// PREVENTIONPLAN LINES
	print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
	print load_fiche_titre($langs->trans("LinkedQuestionsList"), '', '');
	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

	// Define colspan for the button 'Add'
	$colspan = 3;

	// Lines
	$object->fetchQuestionsLinked($sheet->id, 'sheet');
	$questionIds = $object->linkedObjectsIds;

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('Description') . '</td>';
	print '<td>' . $langs->trans('PhotoOk') . '</td>';
	print '<td>' . $langs->trans('PhotoKo') . '</td>';
	print '<td class="center">' . $langs->trans('Answer') . '</td>';
	print '<td>' . '</td>';
	print '</tr>';

	if ( ! empty($questionIds['question']) && $questionIds > 0) {
		print '<tr>';
		foreach ($questionIds['question'] as $questionId) {
			$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
			if ($result > 0 && is_array($result)) {
				$itemControlDet = array_shift($result);
				$answer = $itemControlDet->answer;
				$comment = $itemControlDet->comment;
			}
			$item = $question;
			$item->fetch($questionId);

			print '<tr>';
			print '<td>';
			print $item->ref;
			print '</td>';

			print '<td>';
			print $item->description;
			print '</td>';

			print '<td>';
			print '<img width="40" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ok/thumbs/' . preg_replace('/\./', '_small.', $item->photo_ok)) . '" >';
			print '</td>';

			print '<td>';
			print '<img width="40" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=dolismq&entity=' . $conf->entity . '&file=' . urlencode($item->element . '/' . $item->ref . '/photo_ko/thumbs/' . preg_replace('/\./', '_small.', $item->photo_ko)) . '" >';
			print '</td>';

			print '<td class="center">';
			print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';

			print '<span class="answer" value="1" '. ($answer == 1 ? 'style="border: solid; border-color: blue"' : '').'>';
			print '<i class="fas fa-check"></i>&nbsp&nbsp&nbsp&nbsp&nbsp';
			print '</span>';

			print '<span class="answer" value="2" '. ($answer == 2 ? 'style="border: solid; border-color: blue"' : '').'>';
			print '<i class="fas fa-times"></i>&nbsp&nbsp&nbsp&nbsp&nbsp';
			print '</span>';

			print '<span class="answer" value="3" '. ($answer == 3 ? 'style="border: solid; border-color: blue"' : '').'>';
			print '<i class="fas fa-tools"></i>&nbsp&nbsp&nbsp&nbsp&nbsp';
			print '</span>';

			print '<span class="answer" value="4" '. ($answer == 4 ? 'style="border: solid; border-color: blue"' : '').'>';
			print 'N/A';
			print '</span>';

			print '</td>';

			print '</tr>';

			print '<td>';
			print $langs->trans('Comment');
			print '</td>';
			print '<td>';
			print '<input class="question-comment" name="comment'. $item->id .'" id="comment'. $item->id .'" value="'. $comment .'">';
			print '</td>';

		}
		print '</tr>';
	}

	print dol_get_fiche_end();

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
