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
 * \file    admin/about.php
 * \ingroup dolismq
 * \brief   About page of module DoliSMQ.
 */

// Load DoliSMQ environment
if (file_exists('../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

require_once '../lib/dolismq.lib.php';
require_once '../core/modules/modDoliSMQ.class.php';

// Global variables definitions
global $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['errors', 'admin']);

// Get parameters
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize objects
// Technical objets
$dolismq = new modDoliSMQ($db);

// View objects
$form = new Form($db);

// Access control
saturne_check_access($user->admin);

/*
 * View
 */

$pageName = "DoliSMQAbout";
$help_url  = 'FR:Module_DoliSMQ';

saturne_header(0,'', $langs->trans($pageName), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ?: DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($pageName), $linkback, 'dolismq_color@dolismq');

// Configuration header
$head = dolismq_admin_prepare_head();
print dol_get_fiche_head($head, 'about', $langs->trans($pageName), -1, 'dolismq_color@dolismq');

print $dolismq->getDescLong();

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
