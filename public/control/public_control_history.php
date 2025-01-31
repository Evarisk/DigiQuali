<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \brief   Public page to view control history
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load DigiQuali environment
if (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../../digiquali/class/control.class.php';
require_once __DIR__ . '/../../../digiquali/class/sheet.class.php';
require_once __DIR__ . '/../../../digiquali/lib/digiquali_sheet.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langsDomains = ['bills', 'contracts', 'orders', 'products', 'projects', 'companies'];
if (isModEnabled('dolicar')) {
    $langsDomains[] = 'dolicar@dolicar';
}
saturne_load_langs($langsDomains);

// Get parameters
$trackId = GETPOST('track_id', 'alpha');
$entity  = GETPOST('entity');
$route   = GETPOST('route');

// Initialize technical objects
$object   = new Control($db);
$category = new Categorie($db);
$sheet    = new Sheet($db);
$project  = new Project($db);
$user     = new User($db);

$hookmanager->initHooks(['publiccontrolhistory', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Load object
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

$routes = [
    'lastControl'          => '/../../core/tpl/digiquali_public_control.tpl.php',
    'controlList'          => '/../../core/tpl/digiquali_public_control_item.tpl.php',
    'controlDocumentation' => '/../../core/tpl/digiquali_public_control_documentation.tpl.php'
];

if (empty($route)) {
    $route = 'lastControl';
} ?>

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
if (isModEnabled('dolicar') && $objectType == 'productlot') {
    print '<a href="' . dol_buildpath('custom/dolicar/public/agenda/public_vehicle_logbook.php?id=' . $objectId . '&entity=' . $entity . '&backtopage=' . urlencode($_SERVER['REQUEST_URI']), 1). '">';
    print '<div class="wpeo-button marginleftonly">' . $langs->trans('PublicVehicleLogBook') . '</div>';
    print '</a>';
}
print '</div>';

print '<div class="public-card__container">';
foreach ($routes as $key => $routeName) {
    if ($route == $key) {
        require_once __DIR__ . $routeName;
    }
}
print '</div>';

llxFooter('', 'public');
$db->close();
