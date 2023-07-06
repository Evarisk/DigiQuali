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
 * \file    lib/dolismq_control.lib.php
 * \ingroup dolismq
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
    global $db, $langs;

    $head[1][0] = dol_buildpath('/dolismq/view/control/control_medias.php', 1) . '?id=' . $object->id;
    $head[1][1] = '<i class="fas fa-file-image pictofixedwidth"></i>' . $langs->trans('Medias');
    $head[1][2] = 'medias';

	// Initialize technical objects
	$controlEquipment = new ControlEquipment($db);

	$controlEquipmentArray = $controlEquipment->fetchFromParent($object->id);
	if (is_array($controlEquipmentArray) && !empty($controlEquipmentArray)) {
		$nbEquipment = count($controlEquipmentArray);
	} else {
		$nbEquipment = 0;
	}

	$head[2][0] = dol_buildpath('/dolismq/view/control/control_equipment.php', 1) . '?id=' . $object->id;
	$head[2][1] = '<i class="fas fa-toolbox pictofixedwidth"></i>' . $langs->trans('ControlEquipment') . '<span class="badge marginleftonlyshort">' . $nbEquipment . '</span>';
	$head[2][2] = 'equipment';

	$moreparam['documentType']       = 'ControlDocument';
    $moreparam['attendantTableMode'] = 'simple';

    return saturne_object_prepare_head($object, $head, $moreparam, true);
}
