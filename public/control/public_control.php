<?php

/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    public/control/public_control.php
 * \ingroup digiquali
 * \brief   Public page to view control.
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged.
	define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website.
	define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip.
	define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load DigiQuali environment.
if (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DigiQuali libraries.
require_once __DIR__ . '/../../../digiquali/class/control.class.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_sheet.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page.
saturne_load_langs(['bills', 'contracts', 'orders', 'products', 'projects', 'companies']);

// Get parameters.
$track_id = GETPOST('track_id', 'alpha');

// Initialize technical objects.
$object = new Control($db);

$hookmanager->initHooks(['publiccontrol']); // Note that conf->hooks_modules contains array.

// Load object.
$object->fetch(0, '', ' AND track_id =' . "'" . $track_id . "'");

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title);

$elementArray = get_sheet_linkable_objects();

$object->fetchObjectLinked('', '', '', 'digiquali_control');

require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';

llxFooter('', 'public');
$db->close();
