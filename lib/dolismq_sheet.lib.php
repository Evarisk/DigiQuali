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
 * \file    lib/dolismq_sheet.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Sheet
 */

/**
 * Prepare array of tabs for Sheet
 *
 * @param	Sheet	$object		    Sheet
 * @return 	array					Array of tabs
 */
function sheetPrepareHead($object)
{
	// Global variables definitions
	global $conf, $langs;

	// Load translation files required by the page
	$langs->load("dolismq@dolismq");

	// Initialize values
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolismq/view/sheet/sheet_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'sheetCard';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/view/control/control_list.php", 1).'?fromid='.$object->id . '&fromtype=fk_sheet';
	$head[$h][1] = $langs->trans("Controls");
	$head[$h][2] = 'control';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/view/sheet/sheet_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'sheetAgenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'sheet@dolismq');

	return $head;
}
