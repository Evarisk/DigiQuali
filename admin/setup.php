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
 * \file    admin/setup.php
 * \ingroup dolismq
 * \brief   DoliSMQ setup config page.
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
if (!$res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

require_once '../lib/dolismq.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("admin", "dolismq@dolismq"));

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Access control
if (!$user->admin) accessforbidden();

/*
 * Actions
 */

if ($action == 'setMediaInfos') {
	$MediaMaxWidthMedium = GETPOST('MediaMaxWidthMedium', 'alpha');
	$MediaMaxHeightMedium = GETPOST('MediaMaxHeightMedium', 'alpha');
	$MediaMaxWidthLarge = GETPOST('MediaMaxWidthLarge', 'alpha');
	$MediaMaxHeightLarge = GETPOST('MediaMaxHeightLarge', 'alpha');
	$DisplayNumberMediaGallery = GETPOST('DisplayNumberMediaGallery', 'alpha');

	if (!empty($MediaMaxWidthMedium) || $MediaMaxWidthMedium === '0') {
		dolibarr_set_const($db, "DOLISMQ_MEDIA_MAX_WIDTH_MEDIUM", $MediaMaxWidthMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightMedium) || $MediaMaxHeightMedium === '0') {
		dolibarr_set_const($db, "DOLISMQ_MEDIA_MAX_HEIGHT_MEDIUM", $MediaMaxHeightMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxWidthLarge) || $MediaMaxWidthLarge === '0') {
		dolibarr_set_const($db, "DOLISMQ_MEDIA_MAX_WIDTH_LARGE", $MediaMaxWidthLarge, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightLarge) || $MediaMaxHeightLarge === '0') {
		dolibarr_set_const($db, "DOLISMQ_MEDIA_MAX_HEIGHT_LARGE", $MediaMaxHeightLarge, 'integer', 0, '', $conf->entity);
	}
	if (!empty($DisplayNumberMediaGallery) || $DisplayNumberMediaGallery === '0') {
		dolibarr_set_const($db, "DOLISMQ_DISPLAY_NUMBER_MEDIA_GALLERY", $DisplayNumberMediaGallery, 'integer', 0, '', $conf->entity);
	}
}

/*
 * View
 */

$page_name = "DoliSMQSetup";
$morejs    = array("/dolismq/js/dolismq.js");

llxHeader('', $langs->trans($page_name), '', '', 0, 0, $morejs);

// Subheader
$linkback = '<a href="'.($backtopage ?: DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'dolismq_color@dolismq');

// Configuration header
$head = dolismqAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "dolismq_color@dolismq");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("DoliSMQSetupPage").'</span><br><br>';

print load_fiche_titre($langs->trans("MediaData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMediaInfos">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans("MediaMaxWidthMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" value="' . $conf->global->DOLISMQ_MEDIA_MAX_WIDTH_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans("MediaMaxHeightMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" value="' . $conf->global->DOLISMQ_MEDIA_MAX_HEIGHT_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans("MediaMaxWidthLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" value="' . $conf->global->DOLISMQ_MEDIA_MAX_WIDTH_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans("MediaMaxHeightLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . $conf->global->DOLISMQ_MEDIA_MAX_HEIGHT_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="DisplayNumberMediaGallery">' . $langs->trans("DisplayNumberMediaGallery") . '</label></td>';
print '<td>' . $langs->trans("DisplayNumberMediaGalleryDescription") . '</td>';
print '<td><input type="number" name="DisplayNumberMediaGallery" value="' . $conf->global->DOLISMQ_DISPLAY_NUMBER_MEDIA_GALLERY . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
