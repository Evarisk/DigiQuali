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
 * \file    public/control/public_control_history.php
 * \ingroup digiquali
 * \brief   Public page to view control history.
 */

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
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiQuali libraries.
require_once __DIR__ . '/../../../digiquali/class/control.class.php';
require_once __DIR__ . '/../../../digiquali/class/sheet.class.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_sheet.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs, $user;


// Load translation files required by the page.
$langsDomains = ['bills', 'contracts', 'orders', 'products', 'projects', 'companies'];
if (isModEnabled('dolicar')) {
    $langsDomains[] = 'dolicar@dolicar';
}
saturne_load_langs($langsDomains);

// Get parameters.
$trackId         = GETPOST('track_id', 'alpha');
$entity          = GETPOST('entity');
$showLastControl = GETPOST('show_last_control');
$showControlList = GETPOST('show_control_list');

// Initialize technical objects.
$object   = new Control($db);
$category = new Categorie($db);
$sheet    = new Sheet($db);
$project  = new Project($db);
$user     = new User($db);

$hookmanager->initHooks(['publiccontrolhistory', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array.

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Load object.
$objectDataJson = base64_decode($trackId);
$objectData     = json_decode($objectDataJson);

$objectType = $objectData->type;
$objectId   = $objectData->id;

$objectLinked = new $objectType($db);
$objectLinked->fetch($objectId);

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title);

$elementArray = get_sheet_linkable_objects();
$linkedObjectsData = $elementArray[$objectType];

if ($showControlList == 1) {
    $showLastControlFirst = 0;
} else if ($showLastControl == 1) {
    $showLastControlFirst = 1;
} else if (getDolGlobalInt('DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY') == 1) {
    $showLastControlFirst = 1;
} else {
    $showLastControlFirst = 0;
}

$objectControlList = $object->fetchAllWithLeftJoin('DESC', 't.rowid', $showLastControlFirst, 0, ['customsql' => 't.rowid = je.fk_target AND t.status >= ' . $object::STATUS_LOCKED . ' AND t.control_date IS NOT NULL'], 'AND', true, 'LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as je on je.sourcetype = "' . $linkedObjectsData['link_name'] . '" AND je.fk_source = ' . $objectId . ' AND je.targettype = "digiquali_control" AND je.fk_target = t.rowid');

if (is_array($objectControlList) && !empty($objectControlList)) {
    print '<div id="publicControlHistory">';
    print '<br>';
    if (getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY') == 1) {
        print '<div class="center">';
        print '<div class="wpeo-button switch-public-control-view '. ($showLastControlFirst ? '' : 'button-grey') .'">';
        print '<input hidden class="public-control-view" value="1">';
        print $langs->trans('LastControl');
        print '</div>';
        print '&nbsp';
        print '<div class="wpeo-button marginleftonly switch-public-control-view '. ($showLastControlFirst ? 'button-grey' : '') .'">';
        print '<input hidden class="public-control-view" value="0">';
        print $langs->trans('ControlList');
        print '</div>';
        if (getDolGlobalInt('DIGIQUALI_SHOW_ADD_CONTROL_BUTTON_ON_PUBLIC_INTERFACE') == 1) {
            $object        = current($objectControlList);
            $cats          = $category->containing($object->id, $object->element);
            $arraySelected = '';
            if (is_array($cats) && !empty($cats)) {
                $arraySelected = '&categories[]=' . implode('&categories[]=', array_column($cats, 'id'));
            }
            $moreParams = '&fk_sheet=' . $object->fk_sheet . '&fk_user_controller=' . $object->fk_user_controller . '&projectid=' . $object->projectid . $arraySelected . '&' . $linkedObjectsData['post_name'] . '=' . $objectId;
            print '<a href="' . dol_buildpath('custom/digiquali/view/control/control_card.php?action=create' . $moreParams, 1). '" target="_blank">';
            print '<div class="wpeo-button marginleftonly"><i class="fas fa-plus pictofixedwidth"></i>' . $langs->trans('New' . ucfirst($object->element)) . '</div>';
            print '</a>';
        }
    }
    if (isModEnabled('dolicar') && $objectType == 'productlot') {
        print '<a href="' . dol_buildpath('custom/dolicar/public/agenda/public_vehicle_logbook.php?id=' . $objectId . '&entity=' . $entity . '&backtopage=' . urlencode($_SERVER['REQUEST_URI']), 1). '">';
        print '<div class="wpeo-button marginleftonly">' . $langs->trans('PublicVehicleLogBook') . '</div>';
        print '</a>';
    }
    print '</div>';

    print '<input hidden name="token" value="'. newToken() .'">';

    if ($showLastControlFirst == 1) {
        $object = array_shift($objectControlList);
        $object->fetchObjectLinked('', '', '', 'digiquali_control');
        $sheet->fetch($object->fk_sheet);
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';
    } elseif ($conf->browser->layout != 'phone') {
        print '<div class="signature-container" style="max-width: 1600px;">';
        print load_fiche_titre($langs->trans('ControlList'), $objectLinked->getNomUrl(1, 'nolink'), $object->picto);
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td class="tdoverflowmax200">';
        print $langs->trans('Ref');
        print '<td class="tdoverflowmax200 center">';
        print $langs->trans('QRCode');
        print '</td><td class="tdoverflowmax200">';
        print $langs->trans('Controller');
        print '</td><td class="tdoverflowmax200">';
        print $langs->trans('Project');
        print '</td><td class="tdoverflowmax200">';
        print $langs->trans('Sheet');
        print '</td><td class="tdoverflowmax200 center">';
        print $langs->trans('Verdict');
        print '</td><td class="tdoverflowmax200 center">';
        print $langs->trans('ControlDate');
        print '</td><td class="tdoverflowmax200 center">';
        print $langs->trans('NextControl');
        print '</td><td class="tdoverflowmax200 center">';
        print $langs->trans('NextControlDate');
        print '</td></tr>';

        foreach($objectControlList as $objectControl) {
            $verdictColor = $objectControl->verdict == 1 ? 'green' : ($objectControl->verdict == 2 ? 'red' : 'grey');

            $user->fetch($objectControl->fk_user_controller);
            $project->fetch($objectControl->projectid);
            $sheet->fetch($objectControl->fk_sheet);

            print '<tr class="oddeven">';
            print '<td class="tdoverflowmax200">';
            print $objectControl->getNomUrl(1, 'nolink', 1);
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control.php?track_id=' . $objectControl->track_id . '&entity=' . $conf->entity, 3);
            print ' <a href="' . $publicControlInterfaceUrl . '" target="_blank"><i class="fas fa-qrcode"></i></a>';
            print '<td class="tdoverflowmax200 center">';
            print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $objectControl->ref . '/qrcode/', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 1, 'control/'. $objectControl->ref . '/qrcode/', $objectControl, '', 0, 0);
            print '</td><td class="tdoverflowmax200">';
            print $user->getNomUrl(1, 'nolink');
            print '</td><td class="tdoverflowmax200">';
            print ($objectControl->projectid > 0 ? img_picto($langs->trans('Project'), 'project', 'class="pictofixedwidth"') . $project->ref : '');
            print '</td><td class="tdoverflowmax200">';
            print $sheet->getNomUrl(1, 'nolink', 1);
            print '</td><td class="tdoverflowmax200 center">';
            print '<div class="wpeo-button button-' . $verdictColor . ' button-square-60">' . $objectControl->fields['verdict']['arrayofkeyval'][(!empty($objectControl->verdict)) ? $objectControl->verdict : 3] . '</div>';
            print '</td><td class="tdoverflowmax200 center">';
            print dol_print_date($objectControl->control_date);
            print '</td>';
            if (dol_strlen($objectControl->next_control_date) > 0) {
                print '<td class="tdoverflowmax200 center">';
                $nextControl = floor(($objectControl->next_control_date - dol_now())/(3600 * 24));
                $nextControlColor = $nextControl < 0 ? 'red' : ($nextControl <= 30 ? 'orange' : ($nextControl <= 60 ? 'yellow' : 'green'));
                print '<div class="wpeo-button center button-' . $nextControlColor . '">' . $nextControl . ' ' . $langs->trans('Days') . '</div>';
                print '</td><td class="tdoverflowmax200 center">';
                print dol_print_date($objectControl->next_control_date);
                print '</td>';
            } else {
                print '<td></td><td></td>';
            }
            print '</tr>';
        }
        print '</table>';
        print '</div>';
    } else {
        // Phone view
        print '<div class="signature-container" style="max-width: 1400px;">';
        print load_fiche_titre($langs->trans('ControlList'), $objectLinked->getNomUrl(1, 'nolink'), $object->picto);
        print '<table class="noborder centpercent">';

        foreach($objectControlList as $objectControl) {
            $verdictColor = $objectControl->verdict == 1 ? 'green' : ($objectControl->verdict == 2 ? 'red' : 'grey');

            $user->fetch($objectControl->fk_user_controller);
            $project->fetch($objectControl->projectid);
            $sheet->fetch($objectControl->fk_sheet);

            print '<tr class="oddeven">';
            print '<td class="tdoverflowmax200">';
            print $objectControl->getNomUrl(1, 'nolink', 1);
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control.php?track_id=' . $objectControl->track_id . '&entity=' . $conf->entity, 3);
            print ' <a href="' . $publicControlInterfaceUrl . '" target="_blank"><i class="fas fa-qrcode"></i></a><br>';
            print $user->getNomUrl(1, 'nolink') . '<br>';
            print ($objectControl->projectid > 0 ? img_picto($langs->trans('Project'), 'project', 'class="pictofixedwidth"') . $project->ref . '<br>' : '');
            print $sheet->getNomUrl(1, 'nolink', 1) . '<br>';
            print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $objectControl->ref . '/qrcode/', 'small', 1, 0, 0, 0, 70, 70, 0, 0, 1, 'control/'. $objectControl->ref . '/qrcode/', $objectControl, '', 0, 0);
            print '</td><td class="tdoverflowmax200 center">';
            print '<div class="wpeo-button button-' . $verdictColor . ' button-square-60">' . $objectControl->fields['verdict']['arrayofkeyval'][(!empty($objectControl->verdict)) ?: 3] . '</div><br>';
            if (dol_strlen($objectControl->next_control_date) > 0) {
                print '<hr><div style="font-size: 8px; font-weight: bold">' . $langs->trans('NextControl') . '<br>';
                $nextControl = floor(($objectControl->next_control_date - dol_now())/(3600 * 24));
                $nextControlColor = $nextControl < 0 ? 'red' : ($nextControl <= 30 ? 'orange' : ($nextControl <= 60 ? 'yellow' : 'green'));
                print dol_print_date($objectControl->next_control_date, 'day') . '<br>' . $langs->trans('Remain') . '<br>';
                print '</div>';
                print '<div class="wpeo-button button-' . $nextControlColor . '" style="padding: 0; font-size: 10px;">' . $nextControl . ' ' . $langs->trans('Days') . '</div>';
            }
            print '</td></tr>';
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
