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
$route           = GETPOST('route');

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

if ($objectLinked->element == 'productlot') {
    $objectLinked->element = 'productbatch';
}
$objectLinked->fetchObjectLinked($objectId, $objectLinked->element, '', 'digiquali_control');

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, [], [], '', 'page-public-card');

$elementArray = get_sheet_linkable_objects();
$linkedObjectsData = $elementArray[$objectType];

if (empty($route)) {
    $route = 'lastControl';
}

$objectControlList = $object->fetchAllWithLeftJoin('DESC', 't.rowid', $route == 'lastControl', 0, ['customsql' => 't.rowid = je.fk_target AND t.status >= ' . $object::STATUS_LOCKED . ' AND t.control_date IS NOT NULL'], 'AND', true, 'LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as je on je.sourcetype = "' . $linkedObjectsData['link_name'] . '" AND je.fk_source = ' . $objectId . ' AND je.targettype = "digiquali_control" AND je.fk_target = t.rowid');

if (is_array($objectControlList) && !empty($objectControlList)) { ?>
    <div class="" id="publicControlHistory">
        <?php if (getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY') == 1) {
        print '<div class="public-card__tab">';
        print '<div class="tab switch-public-control-view '. ($route == 'lastControl' ? 'tab-active' : '') .'" data-route="lastControl">';
        print $langs->trans('Status') . ' : ' . $langs->transnoentities($linkedObjectsData['langs']);
        print '</div>';
        print '<div class="tab switch-public-control-view '. ($route == 'controlList' ? 'tab-active' : '') .'" data-route="controlList">';
        print $langs->trans('ControlList');
        print '</div>';
        print '<div class="tab switch-public-control-view '. ($route == 'controlDocumentation' ? 'tab-active' : '') .'" data-route="controlDocumentation">';
        print $langs->trans('Documentation');
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

    $object = array_shift($objectControlList);
    //$object->fetchObjectLinked('', '', '', 'digiquali_control');
    $sheet->fetch($object->fk_sheet);
    print '<div class="public-card__container flex flex-col" style="max-width: 1000px; gap: 10px;">';
    if ($route == 'lastControl') {
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';
        $displayLastControl = 1;
    } elseif ($route == 'controlList') {
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control_item.tpl.php';

        foreach($objectControlList as $object) {

            $object->fetchObjectLinked('', '', '', 'digiquali_control');
            $sheet->fetch($object->fk_sheet);

            require __DIR__ . '/../../core/tpl/digiquali_public_control_item.tpl.php';

        }
    } elseif ($route == 'controlDocumentation') {
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control.tpl.php';
        require_once __DIR__ . '/../../core/tpl/digiquali_public_control_documentation.tpl.php';
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
