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
} elseif (file_exists('../../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../lib/dolismq.lib.php';
require_once __DIR__ . '/../class/question.class.php';

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

$error = 0; // Error counter

// Initialize objects
$object = new Question($db);
$elementType = $object->element;
$objectType  = $object->element;
$elementtype = $moduleNameLowerCase . '_' . $objectType; // Must be the $table_element of the class that manage extrafield.

// View objects
$form = new Form($db);

// Access control
$permissiontoread = $user->rights->dolismq->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

//Extrafields actions
require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

//Set numering modele for control object
if ($action == 'setmod') {
	$constforval = 'DOLISMQ_' . strtoupper('question') . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$helpUrl = 'FR:Module_DoliSMQ';
$title    = $langs->trans('ModuleSetup', $moduleName);

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = dolismq_admin_prepare_head();
print dol_get_fiche_head($head, 'question', $title, -1, "dolismq_color@dolismq");

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

if ($object->isextrafieldmanaged > 0) {
	require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_extrafields_view.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
