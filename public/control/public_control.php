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
 * \ingroup dolismq
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

// Load DoliSMQ environment.
if (file_exists('../../dolismq.main.inc.php')) {
    require_once __DIR__ . '/../../dolismq.main.inc.php';
} elseif (file_exists('../../../dolismq.main.inc.php')) {
    require_once __DIR__ . '/../../../dolismq.main.inc.php';
} else {
    die('Include of dolismq main fails');
}

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DoliSMQ libraries.
require_once __DIR__ . '/../../../dolismq/class/control.class.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page.
saturne_load_langs(['bills', 'contracts', 'orders', 'products', 'projects']);

// Get parameters.
$id       = GETPOST('id', 'int');
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'aZ09');

// Initialize technical objects.
$object = new Control($db);

// Load object.
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title);

$objectLinked    = '';
$objectInfoArray = [];
$qcFrequency     = 0;
$object->fetchObjectLinked('', '', '', 'dolismq_control');
foreach ($object->linkedObjectsIds as $key => $linkedObjects) {
    // Special case
    if ($key == 'productbatch') {
        $productlot = new Productlot($db);
        $productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
        $objectInfoArray['productbatch'] = ['title' => 'Batch', 'value' => img_picto('', 'productlot', 'class="pictofixedwidth"') . $productlot->batch, 'qc_frequency' => $productlot->array_options['options_qc_frequency']];
    } elseif (!empty($object->linkedObjects[$key])) {
        $linkedObject = array_values($object->linkedObjects[$key])[0];
        $objectInfoArray = [
            'product'      => ['title' => 'Product',    'value' => img_picto('', 'product', 'class="pictofixedwidth"') . $linkedObject->ref],
            'user'         => ['title' => 'User',       'value' => img_picto('', 'user', 'class="pictofixedwidth"') . strtoupper($linkedObject->lastname) . ' ' . $linkedObject->firstname],
            'societe'      => ['title' => 'ThirdParty', 'value' => img_picto('', 'company', 'class="pictofixedwidth"')  . $linkedObject->name],
            'contact'      => ['title' => 'Contact',    'value' => img_picto('', 'contact', 'class="pictofixedwidth"')  . strtoupper($linkedObject->lastname) . ' ' . $linkedObject->firstname],
            'project'      => ['title' => 'Project',    'value' => img_picto('', 'project', 'class="pictofixedwidth"') . $linkedObject->ref],
            'project_task' => ['title' => 'Task',       'value' => img_picto('', 'task', 'class="pictofixedwidth"') . $linkedObject->ref],
            'facture'      => ['title' => 'Bill',       'value' => img_picto('', 'bill', 'class="pictofixedwidth"') . $linkedObject->ref],
            'commande'     => ['title' => 'Order',      'value' => img_picto('', 'order', 'class="pictofixedwidth"') . $linkedObject->ref],
            'contrat'      => ['title' => 'Contract',   'value' => img_picto('', 'contract', 'class="pictofixedwidth"') . $linkedObject->ref],
            'ticket'       => ['title' => 'Ticket',     'value' => img_picto('', 'ticket', 'class="pictofixedwidth"') . $linkedObject->ref],
        ];
        $objectInfoArray[$key]['qc_frequency'] = $linkedObject->array_options['options_qc_frequency'];
    }
    if (isset($objectInfoArray[$key]['qc_frequency']) && $qcFrequency < $objectInfoArray[$key]['qc_frequency']){
        $qcFrequency = $objectInfoArray[$key]['qc_frequency'];
        $objectLinked .= '<strong>' . $langs->transnoentities($objectInfoArray[$key]['title']) . ' : ' . $objectInfoArray[$key]['value'] . '</strong><br>';
    } else {
        $objectLinked .= $langs->transnoentities($objectInfoArray[$key]['title']) . ' : ' . $objectInfoArray[$key]['value'] . '<br>';
    }
}


?>

<div class="signature-container">
    <div class="wpeo-gridlayout grid-2">
        <div class=""><?php echo saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
        <div class="informations">
            <strong><?php echo $object->getNomUrl(1, 'nolink'); ?></strong>
            <div class="wpeo-table table-flex table-3">
                <div class="table-row">
                    <div class="table-cell"><?php echo '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict'); ?></div>
                    <div class="table-cell table-end"><?php echo (!empty($object->verdict) ? $langs->transnoentities($object->fields['verdict']['arrayofkeyval'][$object->verdict]) : $langs->transnoentities('NoVerdict')); ?></div>
                </div>
                <div class="table-row">
                    <div class="table-cell"><?php echo $langs->trans('ObjectLinked'); ?></div>
                    <div class="table-cell table-250 table-end"><?php echo $objectLinked; ?></div>
                </div>
                <div class="table-row">
                    <div class="table-cell table-200"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('ControlDate'); ?></div>
                    <div class="table-cell table-end"><?php echo dol_print_date($object->date_creation, 'day'); ?></div>
                </div>
                <?php if ($qcFrequency > 0) : ?>
                    <div class="table-row">
                        <div class="table-cell table-300"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('NextControlDate'); ?></div>
                        <?php $nextControlDate = dol_time_plus_duree($object->date_creation, $qcFrequency, 'd'); ?>
                        <div class="table-cell table-end"><?php echo dol_print_date($nextControlDate, 'day'); ?></div>
                    </div>
                    <div class="table-row">
                        <div class="table-cell table-200"><?php echo img_picto('', $object->picto, 'class="pictofixedwidth"') . $langs->trans('NextControl'); ?></div>
                        <div class="table-cell table-75 table-end badge badge-status <?php echo ((($nextControlDate - $object->date_creation) > 0) ? 'badge-status4' : 'badge-status8'); ?>"><?php echo floor(($nextControlDate - $object->date_creation)/(3600 * 24)) . ' ' . $langs->trans('Days'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php llxFooter('', 'public');
$db->close();