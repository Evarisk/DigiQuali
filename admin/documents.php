<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    admin/documents.php
 * \ingroup dolismq
 * \brief   DoliSMQ documents page.
 */

// Load Dolibarr environment
if (file_exists("../dolismq.main.inc.php")) $res = @include "../dolismq.main.inc.php";

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once '../lib/dolismq.lib.php';

// Translations
saturne_load_langs(['admin']);

// Access control
$permissiontoread = $user->admin;
saturne_check_access($permissiontoread);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$type       = GETPOST('type', 'alpha');
$const 		= GETPOST('const', 'alpha');
$label 		= GETPOST('label', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

/*
 * Actions
 */

if ($action == 'deletefile' && $modulepart == 'ecm' && !empty($user->admin)) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');

	$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
	foreach ($listofdir as $key => $tmpdir) {
		$tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
		if (!$tmpdir) {
			unset($listofdir[$key]);
			continue;
		}
		$tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
		if (!is_dir($tmpdir)) {
			if (empty($nomessageinsetmoduleoptions)) {
				setEventMessages($langs->trans('ErrorDirNotFound', $tmpdir), null, 'warnings');
			}
		} else {
			$upload_dir = $tmpdir;
			break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
		}
	}

	$filetodelete = $tmpdir.'/'.GETPOST('file');
	$result = dol_delete_file($filetodelete);
	if ($result > 0) {
		setEventMessages($langs->trans('FileWasRemoved', GETPOST('file')), null, 'mesgs');
		header('Location: ' . $_SERVER['PHP_SELF']);
	}
}

// Activate a model
if ($action == 'set') {
	addDocumentModel($value, $type, $label, $const);
	header('Location: ' . $_SERVER['PHP_SELF']);
} elseif ($action == 'del') {
	$constforval = 'DOLISMQ_' .strtoupper($type). '_DEFAULT_MODEL';
	if ($value == dolibarr_get_const($db, $constforval)) {
		dolibarr_del_const($db, $constforval);
	}
	delDocumentModel($value, $type);
	header('Location: ' . $_SERVER['PHP_SELF']);
}

// Set default model
if ($action == 'setdoc') {
	$constforval = 'DOLISMQ_' .strtoupper($type). '_DEFAULT_MODEL';
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $const);
	}
} elseif ($action == 'setmod') {
	$constforval = 'DOLISMQ_'.strtoupper($type). '_ADDON';
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setModuleOptions') {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');

	$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
	foreach ($listofdir as $key => $tmpdir) {
		$tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
		if (!$tmpdir) {
			unset($listofdir[$key]);
			continue;
		}
		$tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
		if (!is_dir($tmpdir)) {
			if (empty($nomessageinsetmoduleoptions)) {
				setEventMessages($langs->trans('ErrorDirNotFound', $tmpdir), null, 'warnings');
			}
		} else {
			$upload_dir = $tmpdir;
			break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
		}
	}

	if (!empty($_FILES)) {
		if (is_array($_FILES['userfile']['tmp_name'])) {
			$userfiles = $_FILES['userfile']['tmp_name'];
		} else {
			$userfiles = array($_FILES['userfile']['tmp_name']);
		}

		foreach ($userfiles as $key => $userfile) {
			if (empty($_FILES['userfile']['tmp_name'][$key])) {
				$error++;
				if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('File')), null, 'errors');
				}
			}
			if (preg_match('/__.*__/', $_FILES['userfile']['name'][$key])) {
				$error++;
				setEventMessages($langs->trans('ErrorWrongFileName'), null, 'errors');
			}
		}

		if (!$error) {
			// Define if we have to generate thumbs or not
			if (GETPOST('section_dir', 'alpha')) {
				$generatethumbs = 0;
			}
			$allowoverwrite = (GETPOST('overwritefile', 'int') ? 1 : 0);

			if (!empty($upload_dirold) && !empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
				$result = dol_add_file_process($upload_dirold, $allowoverwrite, 1, 'userfile', GETPOST('savingdocmask', 'alpha'), null, '', $generatethumbs, $object);
			} elseif (!empty($tmpdir)) {
				$result = dol_add_file_process($tmpdir, $allowoverwrite, 1, 'userfile', GETPOST('savingdocmask', 'alpha'), null, '' );
			}
		}
	}
}

/*
 * View
 */

$help_url = '';
$title    = $langs->trans('YourDocuments');
saturne_header(0,'', $title, $help_url, '', '', '');

$types = array(
	'ControlDocument' => 'controldocument',
);

$pictos = array(
	'ControlDocument' => '<i class="fas fa-tasks"></i> ',
);

// Subheader
$selectorAnchor = '<select onchange="location = this.value;">';
foreach ($types as $type => $documentType) {
	$selectorAnchor .= '<option value="#' . $langs->trans($type) . '">' . $langs->trans($type) . '</option>';
}
$selectorAnchor .= '</select>';

print load_fiche_titre($title, $selectorAnchor, 'dolismq_color@dolismq');

// Configuration header
$head = dolismq_admin_prepare_head();
print dol_get_fiche_head($head, 'documents', $title, -1, 'dolismq_color@dolismq');

print load_fiche_titre($langs->trans("DocumentsConfig"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print  $langs->trans("AutomaticPdfGeneration");
print '</td><td>';
print $langs->trans('AutomaticPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLISMQ_AUTOMATIC_PDF_GENERATION');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print  $langs->trans("ManualPdfGeneration");
print '</td><td>';
print $langs->trans('ManualPdfGenerationDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLISMQ_MANUAL_PDF_GENERATION');
print '</td>';
print '</tr>';

print '</table>';

foreach ($types as $type => $documentType) {

	print load_fiche_titre($pictos[$type] . $langs->trans($type), '', '', 0, $langs->trans($type));
	print '<hr>';

	if ($type == 'ControlDocument') {

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

	$trad = 'DoliSMQ' . $type . 'DocumentNumberingModule';
	print load_fiche_titre($langs->trans($trad), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Name').'</td>';
	print '<td>'.$langs->trans('Description').'</td>';
	print '<td class="nowrap">'.$langs->trans('Example').'</td>';
	print '<td class="center">'.$langs->trans('Status').'</td>';
	print '<td class="center">'.$langs->trans('ShortInfo').'</td>';
	print '</tr>';

	clearstatcache();

	$dir = dol_buildpath('/custom/dolismq/core/modules/dolismq/dolismqdocuments/' .$documentType. '/');
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
							print '</td><td>';
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
								print img_picto($langs->trans('Activated'), 'switch_on');
							}
							else {
								print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&const='.$module->scandir.'&label='.urlencode($module->name).'&token=' . newToken() . '#' . $langs->trans($type) . '"  alt="'.$langs->trans('Default').'">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
							}
							print '</td>';

							// Example for listing risks action
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans('Version').': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($module);
							if ("$nextval" != $langs->trans('NotAvailable')) {  // Keep " on nextval
								$htmltooltip .= $langs->trans('NextValue').': ';
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
							print '</tr>';
						}
					}
				}
			}
			closedir($handle);
		}
	}

	$trad = 'DoliSMQTemplateDocument' . $type;
	print load_fiche_titre($langs->trans($trad), '', '');

	// Defini tableau def des modeles
	$def = array();
	$sql = 'SELECT nom';
	$sql .= ' FROM ' .MAIN_DB_PREFIX. 'document_model';
	$sql .= " WHERE type = '" . $documentType . "'";
	$sql .= ' AND entity = ' .$conf->entity;
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
	print '<td>'.$langs->trans('Name').'</td>';
	print '<td>'.$langs->trans('Description').'</td>';
	print '<td class="center">'.$langs->trans('Status'). '</td>';
	print '<td class="center">'.$langs->trans('Default'). '</td>';
	print '<td class="center">'.$langs->trans('ShortInfo').'</td>';
	print '<td class="center">'.$langs->trans('Preview').'</td>';
	print '</tr>';

	clearstatcache();

	$dir = dol_buildpath('/custom/dolismq/core/modules/dolismq/dolismqdocuments/' .$documentType. '/');
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				$filelist[] = $file;
			}
			closedir($handle);
			arsort($filelist);

			foreach ($filelist as $file) {
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/' . $documentType . '/i', $file) && preg_match('/odt|pdf/i', $file)) {
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
							print '</td><td>';
							if (method_exists($module, 'info')) print $module->info($langs);
							else print $module->description;
							print '</td>';

							// Active
							if (in_array($name, $def)) {
								print '<td class="center">';
								print '<a href="'.$_SERVER['PHP_SELF'].'?action=del&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'&type='.$documentType.'&token=' . newToken() . '#' . $langs->trans($type) . '">';
								print img_picto($langs->trans('Enabled'), 'switch_on');
								print '</a>';
								print '</td>';
							}
							else
							{
								print '<td class="center">';
								print '<a href="'.$_SERVER['PHP_SELF'].'?action=set&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'&type='.$documentType.'&token=' . newToken() . '#' . $langs->trans($type) . '">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
								print '</td>';
							}

							// Default
							print '<td class="center">';
							if ($documentType != 'projectdocument') {
								$defaultModelConf = 'DOLISMQ_' . strtoupper($documentType) . '_DEFAULT_MODEL';
							} else {
								$defaultModelConf = 'PROJECT_ADDON_PDF';
							}

							if ($conf->global->$defaultModelConf == $name) {
								print img_picto($langs->trans('Default'), 'on');
							}
							else {
								print '<a href="'.$_SERVER['PHP_SELF'].'?action=setdoc&amp;value='.$name.'&amp;const='.$module->scandir.'&amp;label='.urlencode($module->name).'&type='.$documentType.'&token=' . newToken() . '#' . $langs->trans($type) . '" alt="'.$langs->trans('Default').'">'.img_picto($langs->trans('Disabled'), 'off').'</a>';
							}
							print '</td>';

							// Info
							$htmltooltip = ''.$langs->trans('Name').': '.$module->name;
							$htmltooltip .= '<br>'.$langs->trans('Type').': '.($module->type ? $module->type : $langs->trans('Unknown'));
							$htmltooltip .= '<br>'.$langs->trans('Width').'/'.$langs->trans('Height').': '.$module->page_largeur.'/'.$module->page_hauteur;
							$htmltooltip .= '<br><br><u>'.$langs->trans('FeaturesSupported').':</u>';
							$htmltooltip .= '<br>'.$langs->trans('Logo').': '.yn($module->option_logo, 1, 1);
							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, -1, 0);
							print '</td>';

							// Preview
							print '<td class="center">';
							if ($module->type == 'pdf') {
								//print '<a href="'.$_SERVER['PHP_SELF'].'?action=specimen&module='.$name.'&type='.$documentType.'&token=' . newToken() . '#' . $langs->trans($type) . '" >'.img_object($langs->trans('Preview'), 'pdf').'</a>';
							}
							else {
								print img_object($langs->trans('PreviewNotAvailable'), 'generic');
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
	print '<hr>';

}
// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

