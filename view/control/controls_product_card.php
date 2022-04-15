<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       control_list.php
 *		\ingroup    dolismq
 *		\brief      List page for control
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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
if ($conf->categorie->enabled) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// load dolismq libraries
require_once __DIR__.'/../../class/control.class.php';

// Load translation files required by the page
$langs->loadLangs(array("dolismq@dolismq", "other", 'stocks', 'productbatch'));

// Get parameters
$id = GETPOST('id', 'int');
$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'controlscard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

// Initialize technical objects
$object = new Control($db);
$product = new Product($db);
$project = new Project($db);
$productlot      = new ProductLot($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('productlotcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($productlot->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($productlot->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($productlot->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

$batch  	= GETPOST('batch', 'alpha');
$productid  = GETPOST('productid', 'int');
$ref        = GETPOST('ref', 'alpha'); // ref is productid_batch

$search_entity = GETPOST('search_entity', 'int');
$search_fk_product = GETPOST('search_fk_product', 'int');
$search_batch = GETPOST('search_batch', 'alpha');
$search_fk_user_creat = GETPOST('search_fk_user_creat', 'int');
$search_fk_user_modif = GETPOST('search_fk_user_modif', 'int');
$search_import_key = GETPOST('search_import_key', 'int');

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'list';
}

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id || $ref) {
	if ($ref) {
		$tmp = explode('_', $ref);
		$productid = $tmp[0];
		$batch = $tmp[1];
	}
	$productlot->fetch($id, $productid, $batch);
	$productlot->ref = $productlot->batch; // For document management ( it use $object->ref)
}

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productlotcard', 'globalcard'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->dolismq->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('controllist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) { reset($object->fields); $sortfield="t.".key($object->fields); }   // Set here default search field. By default 1st field in definition. Reset is required to avoid key() to return null.
if (!$sortorder) $sortorder = "ASC";

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>($visible != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=>$val['help']
		);
	}
}

$arrayfields['t.status']['checked'] = 0;

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->dolismq->control->read;
$permissiontoadd = $user->rights->dolismq->control->write;
$permissiontodelete = $user->rights->dolismq->control->delete;

// Security check
if (empty($conf->dolismq->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'dolismq', $id, '');
//if (!$permissiontoread) accessforbidden();



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Control';
	$objectlabel = 'Control';
	$uploaddir = $conf->dolismq->dir_output;
	include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}

/*
 * View
 */

$now = dol_now();
$form = new Form($db);
$socstatic = new Societe($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);
$userstatic = new User($db);

$title    = $langs->trans("ControlList");

llxHeader("", $title, $help_url);


if ($id > 0 || !empty($ref)) {
	// Fiche en mode visu

	$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) {
		$showbarcode = 0;
	}

	$head = product_prepare_head($product);
	$titre = $langs->trans("CardProduct".$product->type);
	$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');

	print dol_get_fiche_head($head, 'controls', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$product->type.'">'.$langs->trans("BackToList").'</a>';
	$product->next_prev_filter = " fk_product_type = ".$product->type;

	$shownav = 1;
	if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
		$shownav = 0;
	}

	dol_banner_tab($product, 'ref', $linkback, $shownav, 'ref');


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Type
	if (!empty($conf->product->enabled) && !empty($conf->service->enabled)) {
		$typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
		print '<tr><td class="titlefield">';
		print (empty($conf->global->PRODUCT_DENY_CHANGE_PRODUCT_TYPE)) ? $form->editfieldkey("Type", 'fk_product_type', $product->type, $product, $usercancreate, $typeformat) : $langs->trans('Type');
		print '</td><td>';
		print $form->editfieldval("Type", 'fk_product_type', $product->type, $product, $usercancreate, $typeformat);
		print '</td></tr>';
	}

	if ($showbarcode) {
		// Barcode type
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("BarcodeType");
		print '</td>';
		if (($action != 'editbarcodetype') && $usercancreate && $createbarcode) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&id='.$product->id.'&token='.newToken().'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbarcodetype' || $action == 'editbarcode') {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
			$formbarcode = new FormBarCode($db);
		}

		$fk_barcode_type = '';
		if ($action == 'editbarcodetype') {
			print $formbarcode->formBarcodeType($_SERVER['PHP_SELF'].'?id='.$product->id, $product->barcode_type, 'fk_barcode_type');
			$fk_barcode_type = $product->barcode_type;
		} else {
			$product->fetch_barcode();
			$fk_barcode_type = $product->barcode_type;
			print $product->barcode_type_label ? $product->barcode_type_label : ($product->barcode ? '<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>' : '');
		}
		print '</td></tr>'."\n";

		// Barcode value
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("BarcodeValue");
		print '</td>';
		if (($action != 'editbarcode') && $usercancreate && $createbarcode) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&id='.$product->id.'&token='.newToken().'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbarcode') {
			$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $product->barcode;
			if (empty($tmpcode) && !empty($modBarCodeProduct->code_auto)) {
				$tmpcode = $modBarCodeProduct->getNextValue($product, $fk_barcode_type);
			}

			print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setbarcode">';
			print '<input type="hidden" name="barcode_type_code" value="'.$product->barcode_type_code.'">';
			print '<input size="40" class="maxwidthonsmartphone" type="text" name="barcode" value="'.$tmpcode.'">';
			print '&nbsp;<input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Modify").'">';
			print '</form>';
		} else {
			print showValueWithClipboardCPButton($product->barcode);
		}
		print '</td></tr>'."\n";
	}

	// Batch number management (to batch)
	if (!empty($conf->productbatch->enabled)) {
		if ($product->isProduct() || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td>';
			print $product->getLibStatut(0, 2);
			print '</td></tr>';
			if ((($product->status_batch == '1' && $conf->global->PRODUCTBATCH_LOT_USE_PRODUCT_MASKS && $conf->global->PRODUCTBATCH_LOT_ADDON == 'mod_lot_advanced')
				|| ($product->status_batch == '2' && $conf->global->PRODUCTBATCH_SN_ADDON == 'mod_sn_advanced' && $conf->global->PRODUCTBATCH_SN_USE_PRODUCT_MASKS))) {
				print '<tr><td>'.$langs->trans("ManageLotMask").'</td><td>';
				print $product->batch_mask;
				print '</td></tr>';
			}
		}
	}

	// Accountancy sell code
	print '<tr><td class="nowrap">';
	print $langs->trans("ProductAccountancySellCode");
	print '</td><td>';
	if (!empty($conf->accounting->enabled)) {
		if (!empty($product->accountancy_code_sell)) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('', $product->accountancy_code_sell, 1);

			print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
		}
	} else {
		print $product->accountancy_code_sell;
	}
	print '</td></tr>';

	// Accountancy sell code intra-community
	if ($mysoc->isInEEC()) {
		print '<tr><td class="nowrap">';
		print $langs->trans("ProductAccountancySellIntraCode");
		print '</td><td>';
		if (!empty($conf->accounting->enabled)) {
			if (!empty($product->accountancy_code_sell_intra)) {
				$accountingaccount2 = new AccountingAccount($db);
				$accountingaccount2->fetch('', $product->accountancy_code_sell_intra, 1);

				print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
			}
		} else {
			print $product->accountancy_code_sell_intra;
		}
		print '</td></tr>';
	}

	// Accountancy sell code export
	print '<tr><td class="nowrap">';
	print $langs->trans("ProductAccountancySellExportCode");
	print '</td><td>';
	if (!empty($conf->accounting->enabled)) {
		if (!empty($product->accountancy_code_sell_export)) {
			$accountingaccount3 = new AccountingAccount($db);
			$accountingaccount3->fetch('', $product->accountancy_code_sell_export, 1);

			print $accountingaccount3->getNomUrl(0, 1, 1, '', 1);
		}
	} else {
		print $product->accountancy_code_sell_export;
	}
	print '</td></tr>';

	// Accountancy buy code
	print '<tr><td class="nowrap">';
	print $langs->trans("ProductAccountancyBuyCode");
	print '</td><td>';
	if (!empty($conf->accounting->enabled)) {
		if (!empty($product->accountancy_code_buy)) {
			$accountingaccount4 = new AccountingAccount($db);
			$accountingaccount4->fetch('', $product->accountancy_code_buy, 1);

			print $accountingaccount4->getNomUrl(0, 1, 1, '', 1);
		}
	} else {
		print $product->accountancy_code_buy;
	}
	print '</td></tr>';

	// Accountancy buy code intra-community
	if ($mysoc->isInEEC()) {
		print '<tr><td class="nowrap">';
		print $langs->trans("ProductAccountancyBuyIntraCode");
		print '</td><td>';
		if (!empty($conf->accounting->enabled)) {
			if (!empty($product->accountancy_code_buy_intra)) {
				$accountingaccount5 = new AccountingAccount($db);
				$accountingaccount5->fetch('', $product->accountancy_code_buy_intra, 1);

				print $accountingaccount5->getNomUrl(0, 1, 1, '', 1);
			}
		} else {
			print $product->accountancy_code_buy_intra;
		}
		print '</td></tr>';
	}

	// Accountancy buy code export
	print '<tr><td class="nowrap">';
	print $langs->trans("ProductAccountancyBuyExportCode");
	print '</td><td>';
	if (!empty($conf->accounting->enabled)) {
		if (!empty($product->accountancy_code_buy_export)) {
			$accountingaccount6 = new AccountingAccount($db);
			$accountingaccount6->fetch('', $product->accountancy_code_buy_export, 1);

			print $accountingaccount6->getNomUrl(0, 1, 1, '', 1);
		}
	} else {
		print $product->accountancy_code_buy_export;
	}
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>'.(dol_textishtml($product->description) ? $product->description : dol_nl2br($product->description, 1, true)).'</td></tr>';

	// Public URL
	if (empty($conf->global->PRODUCT_DISABLE_PUBLIC_URL)) {
		print '<tr><td>'.$langs->trans("PublicUrl").'</td><td>';
		print dol_print_url($product->url);
		print '</td></tr>';
	}

	// Default warehouse
	if ($product->isProduct() && !empty($conf->stock->enabled)) {
		$warehouse = new Entrepot($db);
		$warehouse->fetch($product->fk_default_warehouse);

		print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
		print (!empty($warehouse->id) ? $warehouse->getNomUrl(1) : '');
		print '</td>';
	}

	// Parent product.
	if (!empty($conf->variants->enabled) && ($product->isProduct() || $product->isService())) {
		$combination = new ProductCombination($db);

		if ($combination->fetchByFkProductChild($product->id) > 0) {
			$prodstatic = new Product($db);
			$prodstatic->fetch($combination->fk_product_parent);

			// Parent product
			print '<tr><td>'.$langs->trans("ParentProduct").'</td><td>';
			print $prodstatic->getNomUrl(1);
			print '</td></tr>';
		}
	}

	print '</table>';
	print '</div>';
	print '<div class="fichehalfright"><div class="ficheaddleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	if ($product->isService()) {
		// Duration
		print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td>'.$product->duration_value.'&nbsp;';
		if ($product->duration_value > 1) {
			$dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hours"), "d"=>$langs->trans("Days"), "w"=>$langs->trans("Weeks"), "m"=>$langs->trans("Months"), "y"=>$langs->trans("Years"));
		} elseif ($product->duration_value > 0) {
			$dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hour"), "d"=>$langs->trans("Day"), "w"=>$langs->trans("Week"), "m"=>$langs->trans("Month"), "y"=>$langs->trans("Year"));
		}
		print (!empty($product->duration_unit) && isset($dur[$product->duration_unit]) ? $langs->trans($dur[$product->duration_unit]) : '')."&nbsp;";

		print '</td></tr>';
	} else {
		if (empty($conf->global->PRODUCT_DISABLE_NATURE)) {
			// Nature
			print '<tr><td class="titlefield">'.$form->textwithpicto($langs->trans("NatureOfProductShort"), $langs->trans("NatureOfProductDesc")).'</td><td>';
			print $product->getLibFinished();
			print '</td></tr>';
		}

		// Brut Weight
		if (empty($conf->global->PRODUCT_DISABLE_WEIGHT)) {
			print '<tr><td class="titlefield">'.$langs->trans("Weight").'</td><td>';
			if ($product->weight != '') {
				print $product->weight." ".measuringUnitString(0, "weight", $product->weight_units);
			} else {
				print '&nbsp;';
			}
			print "</td></tr>\n";
		}

		if (empty($conf->global->PRODUCT_DISABLE_SIZE)) {
			// Brut Length
			print '<tr><td>'.$langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height").'</td><td>';
			if ($product->length != '' || $product->width != '' || $product->height != '') {
				print $product->length;
				if ($product->width) {
					print " x ".$product->width;
				}
				if ($product->height) {
					print " x ".$product->height;
				}
				print ' '.measuringUnitString(0, "size", $product->length_units);
			} else {
				print '&nbsp;';
			}
			print "</td></tr>\n";
		}
		if (empty($conf->global->PRODUCT_DISABLE_SURFACE)) {
			// Brut Surface
			print '<tr><td>'.$langs->trans("Surface").'</td><td>';
			if ($product->surface != '') {
				print $product->surface." ".measuringUnitString(0, "surface", $product->surface_units);
			} else {
				print '&nbsp;';
			}
			print "</td></tr>\n";
		}
		if (empty($conf->global->PRODUCT_DISABLE_VOLUME)) {
			// Brut Volume
			print '<tr><td>'.$langs->trans("Volume").'</td><td>';
			if ($product->volume != '') {
				print $product->volume." ".measuringUnitString(0, "volume", $product->volume_units);
			} else {
				print '&nbsp;';
			}
			print "</td></tr>\n";
		}

		if (!empty($conf->global->PRODUCT_ADD_NET_MEASURE)) {
			// Net Measure
			print '<tr><td class="titlefield">'.$langs->trans("NetMeasure").'</td><td>';
			if ($product->net_measure != '') {
				print $product->net_measure." ".measuringUnitString($product->net_measure_units);
			} else {
				print '&nbsp;';
			}
			print '</td></tr>';
		}
	}

	// Unit
	if (!empty($conf->global->PRODUCT_USE_UNITS)) {
		$unit = $product->getLabelOfUnit();

		print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td><td>';
		if ($unit !== '') {
			print $langs->trans($unit);
		}
		print '</td></tr>';
	}

	// Custom code
	if (!$product->isService() && empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO)) {
		print '<tr><td>'.$langs->trans("CustomCode").'</td><td>'.$product->customcode.'</td>';

		// Origin country code
		print '<tr><td>'.$langs->trans("Origin").'</td><td>'.getCountry($product->country_id, 0, $db);
		if (!empty($product->state_id)) {
			print ' - '.getState($product->state_id, 0, $db);
		}
		print '</td>';
	}

	// Quality Control
	if (!empty($conf->global->PRODUCT_LOT_ENABLE_QUALITY_CONTROL)) {
		print '<tr><td>'.$langs->trans("LifeTime").'</td><td">'.$product->lifetime.'</td></tr>';
		print '<tr><td>'.$langs->trans("QCFrequency").'</td><td>'.$product->qc_frequency.'</td></tr>';
	}

	// Other attributes
	$parameters = array();
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($product->id, Categorie::TYPE_PRODUCT, 1);
		print "</td></tr>";
	}

	// Note private
	if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		print '<!-- show Note --> '."\n";
		print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td><td>'.(dol_textishtml($product->note_private) ? $product->note_private : dol_nl2br($product->note_private, 1, true)).'</td></tr>'."\n";
		print '<!-- End show Note --> '."\n";
	}

	print "</table>\n";
	print '</div>';

	print '</div></div>';
	print '<div style="clear:both"></div>';

	print dol_get_fiche_end();
}

$formconfirm = '';

// Confirmation to delete
if ($action == 'delete') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$project->id, $langs->trans('DeleteBatch'), $langs->trans('ConfirmDeleteBatch'), 'confirm_delete', '', 0, 1);
}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $productlot, $action); // Note that $action and $product may have been modified by hook
if (empty($reshook)) {
	$formconfirm .= $hookmanager->resPrint;
} elseif ($reshook > 0) {
	$formconfirm = $hookmanager->resPrint;
}

// Print form confirm
print $formconfirm;

$search['fk_product'] = $id;

//$sqlfilter = ' AND t.fk_product ='.$id;

$newcardbutton = dolGetButtonTitle($langs->trans('NewControl'), '', 'fa fa-plus-circle', dol_buildpath('/dolismq/view/control/control_card.php', 1).'?action=create&fk_product='.$id,'', $permissiontoadd);

include_once '../../core/tpl/dolismq_control_list.tpl.php';

// End of page
llxFooter();
$db->close();
