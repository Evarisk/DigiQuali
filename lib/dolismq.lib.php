<?php
/* Copyright (C) 2021 SuperAdmin
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
 * \brief   Library files with common functions for DoliSMQ
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function dolismqAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("dolismq@dolismq");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolismq/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/dolismq/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/dolismq/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/question.php", 1);
	$head[$h][1] = $langs->trans("Question");
	$head[$h][2] = 'question';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/sheet.php", 1);
	$head[$h][1] = $langs->trans("Sheet");
	$head[$h][2] = 'sheet';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/control.php", 1);
	$head[$h][1] = $langs->trans("Control");
	$head[$h][2] = 'control';
	$h++;

	$head[$h][0] = dol_buildpath("/dolismq/admin/controldocument.php", 1);
	$head[$h][1] = $langs->trans("ControlDocument");
	$head[$h][2] = 'controldocument';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolismq');

	return $head;
}
