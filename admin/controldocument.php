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
 * \file    admin/controldocument/controldocument.php
 * \ingroup dolismq
 * \brief   DoliSMQ controldocument config page.
 */

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
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once '../lib/dolismq.lib.php';
require_once '../class/control.class.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("admin", "dolismq@dolismq"));

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$type       = GETPOST('type', 'alpha');
$const 		= GETPOST('const', 'alpha');
$label 		= GETPOST('label', 'alpha');
$modele     = GETPOST('module', 'alpha');

// Initialize objects
// Technical objets
$control = new Control($db);

// View objects
$form = new Form($db);

// Access control
if (!$user->admin) accessforbidden();

/*
 * Actions
 */

// Activate a model
if ($action == 'set') {
	addDocumentModel($value, $type, $label, $const);
	header("Location: " . $_SERVER["PHP_SELF"]);
} elseif ($action == 'del') {
	delDocumentModel($value, $type);
	header("Location: " . $_SERVER["PHP_SELF"]);
}

// Generate a specimen PDF
if ($action == 'specimen') {
	$control->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath('/custom'.$reldir."core/modules/dolismq/controldocument/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	//Generate specimen file
	if ($filefound) {
		require_once $file;
		$module = new $classname($db);
		if ($module->write_file($control, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=dolismq&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($control->error, $control->errors, 'errors');
			dol_syslog($control->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Set default model Or set numering module
if ($action == 'setdoc') {
	$constforval = "DOLISMQ_CONTROLDOCUMENT_DEFAULT_MODEL";
	$label       = '';

	if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
		$conf->global->$constforval = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label);
	}
} elseif ($action == 'setmod') {
	$constforval = 'DOLISMQ_'.strtoupper($type)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$help_url = 'FR:Module_DoliSMQ';
$title    = $langs->trans("ControlDocument");
$morejs   = array("/dolismq/js/dolismq.js");
$morecss  = array("/dolismq/css/dolismq.css");

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ?: DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'dolismq_color@dolismq');

// Configuration header
$head = dolismqAdminPrepareHead();
print dol_get_fiche_head($head, 'controldocument', '', -1, "dolismq_color@dolismq");

$types = array(
	'ControlDocument' => 'controldocument'
);

$pictos = array(
	'ControlDocument' => '<i class="fas fa-file"></i>'
);

foreach ($types as $type => $documentType) {
	print load_fiche_titre($pictos[$type] . $langs->trans($type), '', '');
	print '<hr>';

	$trad = 'DoliSMQ' . $type . 'DocumentNumberingModule';
	print load_fiche_titre($langs->trans($trad), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '<td class="center">'.$langs->trans("Status").'</td>';
	print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>';

	clearstatcache();
	$dir = dol_buildpath("/custom/dolismq/core/modules/dolismq/".$documentType."/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false ) {
				if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
					$filebis = $file;

					$classname = preg_replace('/\.php$/', '', $file);
					$classname = preg_replace('/\-.*$/', '', $classname);

					if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
						// Charging the numbering class
						require_once $dir.$filebis;

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
							if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
							elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
							else print $tmp;
							print '</td>';

							print '<td class="center">';
							$confType = 'DOLISMQ_' . strtoupper($documentType) . '_ADDON';
							if ($conf->global->$confType == $file || $conf->global->$confType.'.php' == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							}
							else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&const='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							// Example for listing risks action
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($module);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValue").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
										$nextval = $langs->trans($nextval);
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							if ($conf->global->$confType.'.php' == $file) { // If module is the one used, we show existing errors
								if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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

	/*
	*  Documents models for Listing Risks Action
	*/
	$trad = "DoliSMQTemplateDocument" . $type;
	print load_fiche_titre($langs->trans($trad), '', '');

	// Defini tableau def des modeles
	$def = array();
	$sql = "SELECT nom";
	$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
	$sql .= " WHERE type = '".$documentType."'";
	$sql .= " AND entity = ".$conf->entity;
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num_rows = $db->num_rows($resql);
		while ($i < $num_rows)
		{
			$array = $db->fetch_array($resql);
			array_push($def, $array[0]);
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="center">'.$langs->trans("Status")."</td>";
	print '<td class="center">'.$langs->trans("Default")."</td>";
	print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
	print '<td class="center">'.$langs->trans("Preview").'</td>';
	print "</tr>";

	clearstatcache();
	$dir = dol_buildpath("/custom/dolismq/core/modules/dolismq/".$documentType."/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				$filelist[] = $file;
			}
			closedir($handle);
			arsort($filelist);

			foreach ($filelist as $file) {
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
					if (file_exists($dir.'/'.$file)) {
						$name = substr($file, 4, dol_strlen($file) - 16);
						$classname = substr($file, 0, dol_strlen($file) - 12);

						require_once $dir.'/'.$file;
						$module = new $classname($db);


						$modulequalified = 1;
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

						if ($modulequalified) {
							print '<tr class="oddeven"><td>';
							print (empty($module->name) ? $name : $module->name);
							print "</td><td>";
							if (method_exists($module, 'info')) print $module->info($langs);
							else print $module->description;
							print '</td>';

							// Active
							if (in_array($name, $def)) {
								print '<td class="center">';
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'&type='.preg_split('/_/',$documentType)[0].'">';
								print img_picto($langs->trans("Enabled"), 'switch_on');
								print '</a>';
								print "</td>";
							}
							else
							{
								print '<td class="center">';
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'&type='.preg_split('/_/',$documentType)[0].'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
								print "</td>";
							}

							// Default
							print '<td class="center">';
							$defaultModelConf = 'DOLISMQ_' . strtoupper($documentType) . '_DEFAULT_MODEL';
							if ($conf->global->$defaultModelConf == $name) {
								print img_picto($langs->trans("Default"), 'on');
							}
							else {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
							}
							print '</td>';

							// Info
							$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
							$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
							$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
							$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
							$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, -1, 0);
							print '</td>';

							// Preview
							print '<td class="center">';
							if ($module->type == 'pdf') {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
							}
							else {
								print img_object($langs->trans("PreviewNotAvailable"), 'generic');
							}
							print '</td>';
							print '</tr>';
						}
					}
				}
			}
		}
	}

	print '</table>';

	//Control document data
	print load_fiche_titre($langs->trans("ControlDocumentData"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '</tr>';

	//Display document medias conf
	print '<tr><td>';
	print $langs->trans('ControlDocumentName');
	print "</td><td>";
	print $langs->trans('ControlDocumentDescription');
	print '</td>';

	print '<td class="center">';
	print ajax_constantonoff('DOLISMQ_CONTROLDOCUMENT_DISPLAY_MEDIAS');
	print '</td>';
	print '</tr>';
	print '</table>';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

