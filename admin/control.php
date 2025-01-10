<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/control.php
 * \ingroup digiquali
 * \brief   DigiQuali control config page
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../class/control.class.php';
require_once __DIR__ . '/../lib/digiquali.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$attrname   = GETPOST('attrname', 'alpha');

// List of supported format type extrafield label
$tmptype2label = ExtraFields::$type2label;
$type2label    = [''];
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->transnoentitiesnoconv($val);
}

// Initialize objects
$object = new Control($db);

$hookmanager->initHooks(['controladmin', 'globalcard']); // Note that conf->hooks_modules contains array

$elementtype = $moduleNameLowerCase . '_' . $object->element; // Must be the $table_element of the class that manage extrafield

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

//Extrafields actions
require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

if ($action == 'update_control_reminder') {
    $reminderFrequency = GETPOST('control_reminder_frequency');
    $reminderType      = GETPOST('control_reminder_type');

    dolibarr_set_const($db, 'DIGIQUALI_CONTROL_REMINDER_FREQUENCY', $reminderFrequency, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIQUALI_CONTROL_REMINDER_TYPE', $reminderType, 'chaine', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_control_color') {
    $digiqualiPassedTimeControlColor  = GETPOST('passedTimeControl');
    $digiqualiUrgentTimeControlColor  = GETPOST('urgentTimeControl');
    $digiqualiMediumTimeControlColor  = GETPOST('mediumTimeControl');
    $digiqualiPerfectTimeControlColor = GETPOST('perfectTimeControl');

    $results = [
        0 => dolibarr_set_const($db, 'DIGIQUALI_PASSED_TIME_CONTROL_COLOR', $digiqualiPassedTimeControlColor, 'chaine', 0, '', $conf->entity),
        1 => dolibarr_set_const($db, 'DIGIQUALI_URGENT_TIME_CONTROL_COLOR', $digiqualiUrgentTimeControlColor, 'chaine', 0, '', $conf->entity),
        2 => dolibarr_set_const($db, 'DIGIQUALI_MEDIUM_TIME_CONTROL_COLOR', $digiqualiMediumTimeControlColor, 'chaine', 0, '', $conf->entity),
        3 => dolibarr_set_const($db, 'DIGIQUALI_PERFECT_TIME_CONTROL_COLOR', $digiqualiPerfectTimeControlColor, 'chaine', 0, '', $conf->entity)
    ];

    foreach ($results as $result) {
        if ($result == -1) {
            setEventMessage('Error', null, 'errors');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiquali_admin_prepare_head();
print dol_get_fiche_head($head, $object->element, $title, -1, 'digiquali_color@digiquali');

/*
 *  Numbering module
 */

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

/*
 * Numbering module line
 */

$object = new ControlLine($db);

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

// Control reminder
print load_fiche_titre($langs->trans('ControlReminder'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_control_reminder">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

// Enable control reminder
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminder');
print '</td><td>';
print $langs->trans('ControlReminderDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIQUALI_CONTROL_REMINDER_ENABLED');
print '</td></tr>';

// Define control reminder frequency in days (ex: 30,60,90)
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminderFrequency');
print '</td><td>';
print $langs->trans('ControlReminderFrequencyDescription');
print '</td>';

print '<td class="center">';
print '<input type="text" name="control_reminder_frequency" value="' . $conf->global->DIGIQUALI_CONTROL_REMINDER_FREQUENCY . '">';
print '</td></tr>';

// Define control reminder type
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminderType');
print '</td><td>';
print $langs->trans('ControlReminderTypeDescription');
print '</td>';

print '<td class="center">';
$controlReminderType = ['browser' => 'Browser', 'email' => 'Email', 'sms' => 'SMS'];
print Form::selectarray('control_reminder_type', $controlReminderType, (!empty($conf->global->DIGIQUALI_CONTROL_REMINDER_TYPE) ? $conf->global->DIGIQUALI_CONTROL_REMINDER_TYPE : $controlReminderType[0]), 0, 0, 0, '', 1);
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';
print '</table>';

// Manage control colors
print load_fiche_titre($langs->transnoentities('ControlColorParam'), '', '');

$parameters = [
    'value' => [
        0 => 'PASSED',
        1 => 'URGENT',
        2 => 'MEDIUM',
        3 => 'PERFECT'
    ],
    'defaultColors' => [
        0 => '#EB4A40',
        1 => '#ED8532',
        2 => '#F4BA40',
        3 => '#6EEA97'
    ]
];

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="color_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_control_color">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td>' . $langs->transnoentities('Description') . '</td>';
print '<td>' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

for ($i = 0; $i < count($parameters['value']); $i++) {
    print '<tr class="oddeven">';
    print '<td>' . $langs->trans(ucfirst(dol_strtolower($parameters['value'][$i])) . 'TimeControlColor') . '</td>';
    print '<td>' . $langs->trans(ucfirst(dol_strtolower($parameters['value'][$i])) . 'TimeControlColorDescription') . '</td>';
    print '<td>';
    print '<input type="color" id="head" name="' . dol_strtolower($parameters['value'][$i]) . 'TimeControl" value="' . getDolGlobalString('DIGIQUALI_' . $parameters['value'][$i] . '_TIME_CONTROL_COLOR') . '" />';
    print '<span class=" marginleftonly nowraponall opacitymedium">' . $langs->trans('Default') . '</span>: <strong>' . $parameters['defaultColors'][$i] . '</strong>';
    print '</td>';
    print '</tr>';
}

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Extrafields control management
print load_fiche_titre($langs->trans('ExtrafieldsControlManagement'), '', '');

$textobject = dol_strtolower($langs->transnoentities('Control'));
require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
