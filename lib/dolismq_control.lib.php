<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \brief   Library files with common functions for Control
 */

/**
 * Prepare array of tabs for Control
 *
 * @param	Control	$object		    Control
 * @return 	array					Array of tabs
 */
function controlPrepareHead($object)
{
	// Global variables definitions
	global $conf, $langs;

	// Load translation files required by the page
	$langs->load("dolismq@dolismq");

	// Initialize variables
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolismq/view/control/control_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'controlCard';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/dolismq/view/control/control_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/dolismq/view/control/control_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'controlAgenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'control@dolismq');

	return $head;
}
