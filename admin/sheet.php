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
 * \file    admin/sheet.php
 * \ingroup dolismq
 * \brief   DoliSMQ sheet config page.
 */

// Load DoliSMQ environment.
if (file_exists('../dolismq.main.inc.php')) {
    require_once __DIR__ . '/../dolismq.main.inc.php';
} elseif (file_exists('../../dolismq.main.inc.php')) {
    require_once __DIR__ . '/../../dolismq.main.inc.php';
} else {
    die('Include of dolismq main fails');
}

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DoliSMQ libraries.
require_once __DIR__ . '/../lib/dolismq.lib.php';
require_once __DIR__ . '/../lib/dolismq_sheet.lib.php';
require_once __DIR__ . '/../class/sheet.class.php';

// Global variables definitions.
global $conf, $db, $langs, $user;

// Load translation files required by the page.
saturne_load_langs(['admin', 'accountancy']);

// Get parameters
$action      = GETPOST('action', 'alpha');
$backtopage  = GETPOST('backtopage', 'alpha');
$value       = GETPOST('value', 'alpha');
$attrname    = GETPOST('attrname', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'dolismqadminsheet'; // To manage different context of search

// Initialize technical objects.
$object = new Sheet($db);
$tags   = new Categorie($db);

// Initialize view objects.
$form = new Form($db);

// List of supported format.
$tmptype2label = ExtraFields::$type2label;
$type2label    = [''];
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$elementtype = 'dolismq_sheet'; // Must be the $table_element of the class that manage extrafield.
$error = 0; // Error counter.

// Security check - Protection if external user.
$permissiontoread = $user->rights->dolismq->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Extrafields actions.
require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

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

	$tags->label = $langs->trans('Logistics');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('ComputerScience');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Default');
	$tags->type  = 'sheet';
	$tags->create($user);

	dolibarr_set_const($db, 'DOLISMQ_SHEET_DEFAULT_TAG', $tags->id, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DOLISMQ_SHEET_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */
$title    = $langs->trans('ModuleSetup', $moduleName);
$help_url = 'FR:Module_DoliSMQ';

saturne_header(0,'', $title, $help_url);

// Subheader.
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header.
$head = dolismq_admin_prepare_head();
print dol_get_fiche_head($head, $object->element, $title, -1, 'dolismq_color@dolismq');

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$constArray[$moduleNameLowerCase] = [
	'UniqueLinkedElement' => [
		'name'        => 'UniqueLinkedElement',
		'description' => 'UniqueLinkedElementDescription',
		'code'        => 'DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT',
	],
];

$linkableObjects = get_sheet_linkable_objects();

if (is_array($linkableObjects) && !empty($linkableObjects)) {
	$constArray[$moduleNameLowerCase] = array_merge($constArray[$moduleNameLowerCase], $linkableObjects);
}

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Generate categories.
print load_fiche_titre($langs->trans('SheetCategories'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
print '</tr>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generateCategories">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

print '<tr><td>' . $langs->trans('GenerateCategories') . '</td>';
print '<td class="center">';
print $conf->global->DOLISMQ_SHEET_TAGS_SET ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
print '</td>';
print '<td class="center">';
print $conf->global->DOLISMQ_SHEET_TAGS_SET ? '<a type="" class=" butActionRefused" value="">'.$langs->trans('Create') . '</a>' : '<input type="submit" class="button" value="'. $langs->trans('Create') .'">' ;
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->trans('CategoriesGeneration'));
print '</td>';
print '</tr>';
print '</form>';
print '</table>';

// Extrafields sheet management.
print load_fiche_titre($langs->trans('ExtrafieldsSheetManagement'), '', '');

require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons.
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field.
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field.
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end.
print dol_get_fiche_end();
llxFooter();
$db->close();
