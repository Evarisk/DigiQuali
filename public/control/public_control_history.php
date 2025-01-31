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
if (!defined('NOLOGIN')) {     // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {   // Do not check IP defined into conf $dolibarr_main_restrict_ip
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
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
if (isModEnabled('dolicar')) {
    $langsDomains[] = 'dolicar@dolicar';
    saturne_load_langs($langsDomains);
}

// Get parameters
$trackId = GETPOST('track_id', 'alpha');
$entity  = GETPOST('entity');
$route   = GETPOSTISSET('route') ? GETPOST('route') : 'linkedObjectAndControl';

// Initialize technical objects
$object = new Control($db);
$sheet  = new Sheet($db);

$hookmanager->initHooks(['publiccontrolhistory', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

// Load entity
if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}
$conf->setEntityValues($db, $entity);

// Load linkable elements
$linkableElements = get_sheet_linkable_objects();

// Load object
$objectDataJson = base64_decode($trackId);
$objectData     = json_decode($objectDataJson);
$objectType     = $objectData->type;
$objectId       = $objectData->id;

$linkedObject = new $objectType($db);

$linkedObject->fetch($objectId);

$linkableElement = $linkableElements[$linkedObject->element];
// TODO voir si on peut pas enlever ce if
if ($linkedObject->element == 'productlot') {
    $linkedObject->element = 'productbatch';
}
$linkedObject->fetchObjectLinked($objectId, $linkedObject->element, '', 'digiquali_control');
if ($linkedObject->element == 'productbatch') {
    $linkedObject->element = 'productlot';
}

// Routes to display different views
$routes = [
    'linkedObjectAndControl' => '/../../core/tpl/frontend/linked_object_and_control_frontend_view.tpl.php',
    'controlList'            => '/../../core/tpl/digiquali_public_control_item.tpl.php',
    'controlDocumentation'   => '/../../core/tpl/digiquali_public_control_documentation.tpl.php'
];

/*
 * View
 */

$title = $langs->transnoentities('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, [], [], '', 'page-public-card'); ?>

<div id="publicControlHistory">
    <div class="public-card__tab">
        <?php if (getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY')) : ?>
            <div class="tab switch-public-control-view <?php print ($route == 'linkedObjectAndControl' ? 'tab-active' : ''); ?>" data-route="linkedObjectAndControl">
                <?php print $langs->transnoentities('Status') . ' : ' . $langs->transnoentities($linkableElement['langs']); ?>
            </div>
            <div class="tab switch-public-control-view <?php print ($route == 'controlList' ? 'tab-active' : ''); ?>" data-route="controlList">
                <?php print $langs->transnoentities('ControlList'); ?>
            </div>
            <div class="tab switch-public-control-view <?php print ($route == 'controlDocumentation' ? 'tab-active' : ''); ?>" data-route="controlDocumentation">
                <?php print $langs->transnoentities('Documentation'); ?>
            </div>
            <?php if (isModEnabled('dolicar') && $objectType == 'productlot') : ?>
                <div class="tab">
                    <a href="<?php print dol_buildpath('custom/dolicar/public/agenda/public_vehicle_logbook.php?id=' . $objectId . '&entity=' . $entity . '&backtopage=' . urlencode($_SERVER['REQUEST_URI']), 1); ?>">
                        <?php print $langs->transnoentities('PublicVehicleLogBook'); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="public-card__container">
        <?php foreach ($routes as $key => $routeName) {
            if ($route == $key) {
                require_once __DIR__ . $routeName;
            }
        } ?>
    </div>
</div><?php

llxFooter('', 'public');
$db->close();
