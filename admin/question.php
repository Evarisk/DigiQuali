<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/question.php
 * \ingroup dolismq
 * \brief   DoliSMQ question config page.
 */

// Load DoliSMQ environment
if (file_exists('../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once '../lib/dolismq.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$attrname   = GETPOST('attrname', 'alpha');

// List of supported format type extrafield label
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
	$type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$elementtype = 'dolismq_question'; //Must be the $table_element of the class that manage extrafield
$error = 0; // Error counter

// Initialize objects
// View objects
$form = new Form($db);

// Access control
saturne_check_access($user->admin);

/*
 * Actions
 */

//Extrafields actions
require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

//Set numering modele for control object
if ($action == 'setmod') {
	$constforval = 'DOLISMQ_' . strtoupper('question') . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$helpUrl = 'FR:Module_DoliSMQ';
$title   = $langs->trans("Question");

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'dolismq_color@dolismq');

// Configuration header
$head = dolismq_admin_prepare_head();
print dol_get_fiche_head($head, 'question', $title, -1, "dolismq_color@dolismq");

print load_fiche_titre($langs->trans("QuestionManagement"), '', '');
print '<hr>';

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DoliSMQQuestionNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/dolismq/core/modules/dolismq/question/");
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
						if ($conf->global->DOLISMQ_QUESTION_ADDON == $file || $conf->global->DOLISMQ_QUESTION_ADDON . '.php' == $file) {
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
						if ($conf->global->DOLISMQ_QUESTION_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
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

//Extrafields question management
print load_fiche_titre($langs->trans("ExtrafieldsQuestionManagement"), '', '');

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
