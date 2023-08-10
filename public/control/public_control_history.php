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
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiQuali libraries.
require_once __DIR__ . '/../../../digiquali/class/control.class.php';
require_once __DIR__ . '/../../../digiquali/class/sheet.class.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_sheet.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page.
saturne_load_langs(['bills', 'contracts', 'orders', 'products', 'projects', 'companies']);

// Get parameters.
$track_id          = GETPOST('track_id', 'alpha');
$show_last_control = GETPOST('show_last_control');
$show_control_list = GETPOST('show_control_list');

// Initialize technical objects.
$object  = new Control($db);


$hookmanager->initHooks(['publiccontrol']); // Note that conf->hooks_modules contains array.

// Load object.
$objectDataJson = base64_decode($track_id);
$objectData     = json_decode($objectDataJson);

$objectType = $objectData->type;
$objectId   = $objectData->id;

$objectLinked = new $objectType($db);
$objectLinked->fetch($objectId);

$linkedObjects = get_sheet_linkable_objects();

$linkedObjectsData = $linkedObjects[$objectType];
/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title);

$elementArray = get_sheet_linkable_objects();

if ($show_control_list == 1) {
    $show_last_control_first = 0;
} else if ($show_last_control == 1) {
    $show_last_control_first = 1;
} else if (getDolGlobalInt('DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY') == 1) {
    $show_last_control_first = 1;
} else {
    $show_last_control_first = 0;
}

$objectControlList = $object->fetchAllWithLeftJoin('DESC','t.control_date',$show_last_control_first == 1,0, ['customsql' => 't.rowid = je.fk_target AND t.status = ' . $object::STATUS_LOCKED], 'AND', true, 'LEFT JOIN llx_element_element as je on je.sourcetype = "' . $linkedObjectsData['link_name'] . '" AND je.fk_source = ' . $objectId . ' AND je.targettype = "digiquali_control" AND je.fk_target = t.rowid' );

if (is_array($objectControlList) && !empty($objectControlList)) {
    print '<div id="publicControlHistory">';
    print '<br>';
    if (getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY') == 1) {
        print '<div class="center">';
        print '<div class="wpeo-button switch-public-control-view '. ($show_last_control_first ? 'button-grey' : '') .'">';
        print '<input hidden class="public-control-view" value="0">';
        print $langs->trans('ControlList');
        print '</div>';
        print '&nbsp';
        print '<div class="wpeo-button switch-public-control-view '. ($show_last_control_first ? '' : 'button-grey') .'">';
        print '<input hidden class="public-control-view" value="1">';
        print $langs->trans('LastControl');
        print '</div>';
        print '</div>';
    }

    print '<input hidden name="token" value="'. newToken() .'">';

    if ($show_last_control_first == 1) {
        $object = array_shift($objectControlList);
        $object->fetchObjectLinked('', '', '', 'digiquali_control');
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';
    } else {

        print '<div class="signature-container" style="max-width: 1000px;">';
        print load_fiche_titre($langs->trans('ControlList'), $objectLinked->getNomUrl(1, 'nolink'), $object->picto);
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Ref');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Controller');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Project');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Sheet');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('DateCreation');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Verdict');
        print '</td><td class="nowraponall tdoverflowmax200">';
        print $langs->trans('Status');
        print '</tr>';

        foreach($objectControlList as $objectControl) {
            $verdictColor = $objectControl->verdict == 1 ? 'green' : ($objectControl->verdict == 2 ? 'red' : 'grey');
            $user         = new User($db);
            $project      = new Project($db);
            $sheet        = new Sheet($db);

            $user->fetch($objectControl->fk_user_controller);
            $project->fetch($objectControl->projectid);
            $sheet->fetch($objectControl->fk_sheet);

            print '<tr class="oddeven">';
            print '<td class="nowraponall tdoverflowmax200">';
            print $objectControl->getNomUrl(1, 'nolink');
            print '</td><td class="nowraponall tdoverflowmax200">';
            print $user->getNomUrl(1, 'nolink');
            print '</td><td class="nowraponall tdoverflowmax200">';
            print $project->getNomUrl(1, 'nolink');
            print '</td><td class="nowraponall tdoverflowmax200">';
            print $sheet->getNomUrl(1, 'nolink');
            print '</td><td class="nowraponall tdoverflowmax200">';
            print dol_print_date($objectControl->date_creation);
            print '</td><td class="nowraponall tdoverflowmax200">';
            print '<div class="wpeo-button button-'. $verdictColor .'">' . $objectControl->fields['verdict']['arrayofkeyval'][(!empty($objectControl->verdict)) ?: 3] . '</div>';
            print '</td><td class="nowraponall tdoverflowmax200">';
            print $objectControl->getLibStatut(5);
            print '</tr>';
        }
        print '</table>';
        print '</div>';
    }
    print '</div>';
} else {
    print '<div class="signature-container" style="max-width: 1000px;">';
    print load_fiche_titre($langs->trans('ControlList'), $objectLinked->getNomUrl(1, 'nolink'), $object->picto);
    print $langs->trans('NoControlOnThisObject');
    print '</div>';
}

llxFooter('', 'public');
$db->close();
