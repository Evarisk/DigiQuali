<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/sheet.php
 * \ingroup dolismq
 * \brief   DoliSMQ sheet config page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once '../lib/dolismq.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("admin", "dolismq@dolismq", "accountancy"));

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$attrname   = GETPOST('attrname', 'alpha');

// Initialize objects
// Technical objets
$tags = new Categorie($db);

// View objects
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
	$type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$elementtype = 'dolismq_sheet'; // Must be the $table_element of the class that manage extrafield
$error = 0; // Error counter

// Access control
if (!$user->admin) accessforbidden();

/*
 * Actions
 */

// Extrafields actions
require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

// Set numering modele for control object
if ($action == 'setmod') {
	$constforval = 'DOLISMQ_' . strtoupper('sheet') . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

// Generate default categories
if ($action == 'generateCategories') {
	$tags->label = $langs->transnoentities('Quality');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('HealthSecurity');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Environment');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Safety');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Regulatory');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('DesignOffice');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Suppliers');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('CommercialDoliSMQ');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Production');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Methods');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Accounting');
	$tags->type  = 'sheet';
	$tags->create($user);

	dolibarr_set_const($db, 'DOLISMQ_SHEET_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
}


/*
 * View
 */

$help_url = 'FR:Module_DoliSMQ';
$title    = $langs->trans("Sheet");
$morejs   = array("/dolismq/js/dolismq.js.php");
$morecss  = array("/dolismq/css/dolismq.css");

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'dolismq@dolismq');

// Configuration header
$head = dolismqAdminPrepareHead();
print dol_get_fiche_head($head, 'sheet', '', -1, "dolismq@dolismq");

print load_fiche_titre($langs->trans("SheetManagement"), '', '');
print '<hr>';

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DoliSMQSheetNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/dolismq/core/modules/dolismq/sheet/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DOLISMQ_SHEET_ADDON == $file || $conf->global->DOLISMQ_SHEET_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($module);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DOLISMQ_SHEET_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

//Sheet data
print load_fiche_titre($langs->trans("SheetData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

// Unique linked element conf
print '<tr><td>';
print $langs->trans('UniqueLinkedElement');
print "</td><td>";
print $langs->trans('UniqueLinkedElementDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT');
print '</td>';
print '</tr>';
print '</table>';

// Generate categories
print load_fiche_titre($langs->trans("SheetCategories"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generateCategories">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

print '<tr><td>' . $langs->trans("GenerateCategories") . '</td>';
print '<td class="center">';
print $conf->global->DOLISMQ_SHEET_TAGS_SET ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
print '</td>';
print '<td class="center">';
print $conf->global->DOLISMQ_SHEET_TAGS_SET ? '<a type="" class=" butActionRefused" value="">'.$langs->trans('Create') .'</a>' : '<input type="submit" class="button" value="'.$langs->trans('Create') .'">' ;
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->trans("CategoriesGeneration"));
print '</td>';
print '</tr>';
print '</form>';
print '</table>';

// Extrafields sheet management
print load_fiche_titre($langs->trans("ExtrafieldsSheetManagement"), '', '');

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

// Buttons
if ($action != 'create' && $action != 'edit') {
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create">'.$langs->trans("NewAttribute").'</a></div>';
	print "</div>";
}

// Creation of an optional field
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewAttribute'));
	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));
	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

