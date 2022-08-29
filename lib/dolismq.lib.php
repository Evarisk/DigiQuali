<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/dolismq.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Admin conf
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function dolismqAdminPrepareHead()
{
	// Global variables definitions
	global $conf, $langs;

	// Load translation files required by the page
	$langs->load("dolismq@dolismq");

	// Initialize values
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolismq/admin/question.php", 1);
	$head[$h][1] = '<i class="fas fa-question"></i>  ' . $langs->trans("Question");
	$head[$h][2] = 'question';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/sheet.php", 1);
	$head[$h][1] = '<i class="fas fa-list"></i>  ' . $langs->trans("Sheet");
	$head[$h][2] = 'sheet';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/control.php", 1);
	$head[$h][1] = '<i class="fas fa-tasks"></i>  ' . $langs->trans("Control");
	$head[$h][2] = 'control';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/controldocument.php", 1);
	$head[$h][1] = '<i class="fas fa-file"></i>  ' . $langs->trans("ControlDocument");
	$head[$h][2] = 'controldocument';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i>  ' . $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/about.php", 1);
	$head[$h][1] = '<i class="fab fa-readme"></i> ' . $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolismq');

	return $head;
}
