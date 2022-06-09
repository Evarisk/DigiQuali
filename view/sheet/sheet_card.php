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
 *   	\file       sheet_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view sheet
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

require_once __DIR__.'/../../class/sheet.class.php';
require_once __DIR__.'/../../class/question.class.php';
require_once __DIR__.'/../../lib/dolismq_sheet.lib.php';
require_once __DIR__.'/../../core/modules/dolismq/sheet/mod_sheet_standard.php';
require_once '../../lib/dolismq_function.lib.php';

global $langs, $conf, $user, $db;

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other", "product"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'sheetcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object      = new Sheet($db);
$question    = new Question($db);
$extrafields = new ExtraFields($db);
$refSheetMod = new $conf->global->DOLISMQ_SHEET_ADDON($db);

$diroutputmassaction = $conf->dolismq->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('sheetcard', 'globalcard')); // Note that conf->hooks_modules contains array

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


$permissiontoread = $user->rights->dolismq->sheet->read;
$permissiontoadd = $user->rights->dolismq->sheet->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->sheet->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->dolismq->sheet->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->dolismq->sheet->write; // Used by the include of actions_dellink.inc.php
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

	$backurlforlist = dol_buildpath('/dolismq/view/sheet/sheet_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/sheet/sheet_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'addQuestion') {
		$questionId = GETPOST('questionId');
		$question->fetch($questionId);
		$question->add_object_linked($object->element,$id);
		setEventMessages($langs->trans('addQuestionLink') . ' ' . $question->ref, array());

		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

	if ($action == 'unlinkQuestion') {
		$questionId = GETPOST('questionId');
		$question->fetch($questionId);
		$question->element = 'dolismq_'.$question->element;
		$question->deleteObjectLinked($id, $object->element);
		setEventMessages($langs->trans('removeQuestionLink') . ' ' . $question->ref, array());

		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

	$triggermodname = 'DOLISMQ_AUDIT_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'add') {
		$showArray = array();
		$elementArray = array(
			'product',
			'productlot',
			'thirdparty',
			'project',
			'task',
		);

		foreach ($elementArray as $element) {
			if ((GETPOST('show_'.$element) == 'on')) {
				$showArray[$element] = 1;
			}
		}

		$object->element_linked = json_encode($showArray);
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
	$trackid = 'sheet'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title         = $langs->trans("Sheet");
$title_create  = $langs->trans("NewSheet");
$title_edit    = $langs->trans("ModifySheet");

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
	$elementArray = array(
		'product' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT,
			'langs' => 'ProductOrService',
			'picto' => 'product'
		),
		'productlot' => array(
			'conf' => $conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT,
			'langs' => 'Lot',
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
		if (!empty($element['conf'])) {
			print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
			print '<input type="checkbox" id="show_'.$key.'" name="show_'.$key.'">';
			print '</td></tr>';
		}
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
	print load_fiche_titre($title_edit, '', "dolismq@dolismq");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
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
	print dol_get_fiche_head($head, 'sheetCard', $langs->trans("Sheet"), -1, "dolismq@dolismq");

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteSheet'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formsheet = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formsheet, 'yes', 1);
	}

	// SetLocked confirmation
	if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
		|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockSheet'), $langs->trans('ConfirmLockSheet', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formsheet = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formsheet, 0, 1, 220);
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

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	// PREVENTIONPLAN LINES
	print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
	print load_fiche_titre($langs->trans("LinkedQuestionsList"), '', '');
	print '<table id="tablelines" class="noborder noshadow">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

	// Define colspan for the button 'Add'
	$colspan = 3;

	// Lines
	$object->fetchQuestionsLinked($id, 'sheet');

	$questionIds = $object->linkedObjectsIds;

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans('Ref') . '</td>';
	print '<td>' . $langs->trans('Label') . '</td>';
	print '<td>' . $langs->trans('Description') . '</td>';
	print '<td>' . $langs->trans('PhotoOk') . '</td>';
	print '<td>' . $langs->trans('PhotoKo') . '</td>';
	print '<td>' . $langs->trans('Status') . '</td>';
	print '<td class="center">' . $langs->trans('Action') . '</td>';
	print '</tr>';

	if ( ! empty($questionIds['dolismq_question']) && $questionIds > 0) {
		print '<tr>';
		foreach ($questionIds['dolismq_question'] as $questionId) {
			$item = $question;
			$item->fetch($questionId);

			print '<tr>';
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
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=unlinkQuestion&questionId=' . $item->id . '">';
			print img_delete();
			print '</a>';
			print '</td>';
			print '</tr>';

			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
		}
		print '</tr>';
	}

	if ($object->status != 2) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addQuestion">';
		print '<input type="hidden" name="id" value="' . $id . '">';

		print '<td class="">';
		print $question->select_question_list(0, 'questionId', '', '1', 0, 0, array(), '', 0, 0, 'disabled', '', false, $questionIds['dolismq_question']);
		print '</td>';
		print '<td>';
		print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Add") . '">';
		print '</td>';

		print '</form>';
		print dol_get_fiche_end();
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
//			// Send
//			if (empty($user->socid)) {
//				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle');
//			}

//			// Back to draft
//			if ($object->status == $object::STATUS_VALIDATED) {
//				print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes', '', $permissiontoadd);
//			}

			// Back to draft
			print '<span class="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? 'actionButtonLock' : '') . '" title="' . (($object->status == 1 && $question->checkQuestionsLocked($questionIds)) ? '' : dol_escape_htmltag($langs->trans("AllQuestionsMustHaveLocked"))) . '">' . $langs->trans("Lock") . '</span>';
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
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			}
		}
		print '</div>'."\n";
	}
}

// End of page
llxFooter();
$db->close();
