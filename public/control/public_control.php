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
require_once __DIR__ . '/../../../dolismq/lib/dolismq_sheet.lib.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page.
saturne_load_langs(['bills', 'contracts', 'orders', 'products', 'projects', 'companies']);

// Get parameters.
$track_id = GETPOST('track_id', 'alpha');

// Initialize technical objects.
$object = new Control($db);

$hookmanager->initHooks(['publiccontrol']); // Note that conf->hooks_modules contains array.

// Load object.
$object->fetch(0, '', ' AND track_id =' . "'" . $track_id . "'");

/*
 * View
 */

$title = $langs->trans('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title);

$elementArray = get_sheet_linkable_objects();

$qcFrequencyArray = [];
$linkedObjects    = [];

$object->fetchObjectLinked('', '', '', 'dolismq_control', 'OR', 1, 'sourcetype', 0);

foreach($elementArray as $linkableElementType => $linkableElement) {
	if ($linkableElement['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableElement['link_name']]))) {
		$className = $linkableElement['className'];
		$linkedObject = new $className($db);

		$linkedObjectKey = array_key_first($object->linkedObjectsIds[$linkableElement['link_name']]);
		$linkedObjectId  = $object->linkedObjectsIds[$linkableElement['link_name']][$linkedObjectKey];

		$result = $linkedObject->fetch($linkedObjectId);
		if ($result > 0) {
			$linkedObjects[$linkableElementType] = $linkedObject;
			if (array_key_exists('options_qc_frequency', $linkedObject->array_options)) {
				if ($linkedObject->array_options['options_qc_frequency'] > 0) {
					$qcFrequencyArray[$linkableElementType] = $linkedObject->array_options['options_qc_frequency'];
				}
			}
		}
	}
}
?>

<div class="signature-container" style="max-width: 1000px;">
    <div class="wpeo-gridlayout grid-2">
        <div style="display: flex; justify-content: center; align-items: center;"><?php echo saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
        <div class="informations">
            <div style="margin-bottom: 10px"><strong><?php echo $object->getNomUrl(1, 'nolink'); ?></strong></div>
            <div class="wpeo-table table-flex">
                <div style="margin-bottom: 10px; margin-top: 10px;"><strong><?php echo $langs->trans('ObjectLinked'); ?></strong></div>
                <div class="table-row">
                    <div class="table-cell"><?php echo '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict'); ?></div>
                    <div class="table-cell table-end"><?php echo (!empty($object->verdict) ? $langs->transnoentities($object->fields['verdict']['arrayofkeyval'][$object->verdict]) : $langs->transnoentities('NoVerdict')); ?></div>
                </div>
                <div class="table-row">
                  <div class="wpeo-table table-cell table-full">
                    <?php
                    foreach ($elementArray as $linkableObjectType => $linkableObject) {
                      if (is_object($linkedObjects[$linkableObjectType])) {
                        if ($linkableObject['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableObject['link_name']]))) {
                          $currentObject = $linkedObjects[$linkableObjectType];
                          $isMinQcFrequency = $linkableObjectType == array_keys($qcFrequencyArray, min($qcFrequencyArray))[0];
                          print '<div class="table-row">';
                          print '<div class="table-cell table-150">';

                          print $isMinQcFrequency ? '<strong>' : '';
                          print $langs->trans($linkableObject['langs']);

                          print '</div>';
                          print '<div class="table-cell table-200 table-end">';

                          print img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"') . $currentObject->ref;
                          if (array_key_exists($linkableObjectType, $qcFrequencyArray) && $qcFrequencyArray[$linkableObjectType] > 0) {
                            print '<br>';
                            print $langs->transnoentities('QcFrequency') . ' : ' . $qcFrequencyArray[$linkableObjectType];
                          }
                          print $isMinQcFrequency ? '</strong>' : '';

                          print '</div>';
                          print '</div>';
                        }
                      }
                    }
                    ?>
                  </div>
                </div>
                <div class="table-row">
                    <div class="table-cell"><?php echo '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict'); ?></div>
                    <div class="table-cell table-end"><?php echo (!empty($object->verdict) ? $langs->transnoentities($object->fields['verdict']['arrayofkeyval'][$object->verdict]) : $langs->transnoentities('NoVerdict')); ?></div>
                </div>
                <div class="table-row">
                    <div class="table-cell table-200"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('ControlDate'); ?></div>
                    <div class="table-cell table-end"><?php echo dol_print_date($object->date_creation, 'day'); ?></div>
                </div>
                <?php if (!empty($qcFrequencyArray)) : ?>
                    <div class="table-row">
                        <div class="table-cell table-300"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('NextControlDate'); ?></div>
                        <?php $nextControlDate = dol_time_plus_duree($object->date_creation, $minQcFrequency, 'd'); ?>
                        <div class="table-cell table-end"><?php echo dol_print_date($nextControlDate, 'day'); ?></div>
                    </div>
                    <div class="table-row">
                        <div class="table-cell table-200"><?php echo img_picto('', $object->picto, 'class="pictofixedwidth"') . $langs->trans('NextControl'); ?></div>
                        <div class="table-cell table-75 table-end badge badge-status <?php echo ((($nextControlDate - dol_now()) > 0) ? 'badge-status4' : 'badge-status8'); ?>"><?php echo floor(($nextControlDate - dol_now())/(3600 * 24)) . ' ' . $langs->trans('Days'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php llxFooter('', 'public');
$db->close();
