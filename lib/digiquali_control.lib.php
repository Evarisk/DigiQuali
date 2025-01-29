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
 * \file    lib/digiquali_control.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Control.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare array of tabs for control.
 *
 * @param  Control $object Control object.
 * @return array           Array of tabs.
 * @throws Exception
 */
function control_prepare_head(Control $object): array
{
    // Global variables definitions.
    global $conf, $db, $langs;

    $head[1][0] = dol_buildpath('/digiquali/view/object_medias.php', 1) . '?id=' . $object->id . '&object_type=control';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-image pictofixedwidth"></i>' . $langs->trans('Medias') : '<i class="fas fa-file-image"></i>';
    $head[1][2] = 'medias';

	// Initialize technical objects
	$controlEquipment = new ControlEquipment($db);

	$controlEquipmentArray = $controlEquipment->fetchFromParent($object->id);
	if (is_array($controlEquipmentArray) && !empty($controlEquipmentArray)) {
		$nbEquipment = count($controlEquipmentArray);
	} else {
		$nbEquipment = 0;
	}

	$head[2][0]  = dol_buildpath('/digiquali/view/control/control_equipment.php', 1) . '?id=' . $object->id;
	$head[2][1]  = $conf->browser->layout != 'phone' ? '<i class="fas fa-toolbox pictofixedwidth"></i>' . $langs->trans('ControlEquipment') : '<i class="fas fa-toolbox"></i>';
    $head[2][1] .= '<span class="badge marginleftonlyshort">' . $nbEquipment . '</span>';
	$head[2][2]  = 'equipment';

	$moreparam['documentType']       = 'ControlDocument';
    $moreparam['attendantTableMode'] = 'simple';

    return saturne_object_prepare_head($object, $head, $moreparam, true);
}

function show_linked_object($objectLinked, array $linkedObjectsData, $elementArray): array
{
    global $db, $langs;

    $out['objectLinked']['title']        = $langs->transnoentities($linkedObjectsData['langs']);
    $out['objectLinked']['name_field']   = img_picto('', $linkedObjectsData['picto'], 'class="pictofixedwidth"') . $objectLinked->{$linkedObjectsData['name_field']};
    $out['objectLinked']['qc_frequency'] = img_picto('', 'history', 'class="pictofixedwidth"') . $objectLinked->array_options['options_qc_frequency'];

    if (isset($linkedObjectsData['fk_parent'])) {
        $linkedObjectParentData = [];
        foreach ($elementArray as $value) {
            if (isset($value['post_name']) && $value['post_name'] === $linkedObjectsData['fk_parent']) {
                $linkedObjectParentData = $value;
                break;
            }
        }

        if (!empty($linkedObjectParentData['class_path'])) {
            require_once DOL_DOCUMENT_ROOT . '/' . $linkedObjectParentData['class_path'];

            $parentLinkedObject = new $linkedObjectParentData['className']($db);

            $parentLinkedObject->fetch($objectLinked->{$linkedObjectsData['fk_parent']});

            $out['parentLinkedObject']['title']      = $langs->transnoentities($linkedObjectParentData['langs']);
            $out['parentLinkedObject']['name_field'] = img_picto('', $linkedObjectParentData['picto'], 'class="pictofixedwidth"') . $parentLinkedObject->{$linkedObjectParentData['name_field']};
        }
    }

    return $out;
}

function show_control_object($objectLinked): array
{
    global $langs;

    $out         = [];
    $lastControl = null;
    foreach ($objectLinked->linkedObjects['digiquali_control'] as $control) {
        if ($control->status < Control::STATUS_LOCKED ||empty($control->control_date)) {
            continue;
        }

        if ($lastControl === null || $control->control_date > $lastControl->control_date) {
            $lastControl = $control;
        }
    }

    if (!empty($lastControl->next_control_date)) {
        $nextControl                             = floor(($lastControl->next_control_date - dol_now('tzuser'))/(3600 * 24));
        $out['nextControl']['title']             = $langs->transnoentities('NextControl');
        $out['nextControl']['next_control_date'] = dol_print_date($lastControl->next_control_date, 'day');
        $out['nextControl']['next_control']      = $langs->transnoentities('In') . ' ' . $nextControl . ' ' . $langs->trans('Days');
    }

    return $out;
}
