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
 * \file    lib/dolismq_question.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Question
 */

/**
 * Prepare array of tabs for Question
 *
 * @param  Question $object		Question
 * @return array				Array of tabs
 */
function questionPrepareHead(Question $object): array
{
	// Global variables definitions
	global $conf, $db, $langs;

	// Load translation files required by the page
	$langs->load('dolismq@dolismq');

	// Initialize values
	$h = 0;
	$head = [];

	$head[$h][0] = dol_buildpath('/dolismq/view/question/question_card.php', 1).'?id='.$object->id;
	$head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Card');
	$head[$h][2] = 'questionCard';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->dolismq->dir_output. '/question/' .dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath('/dolismq/view/question/question_document.php', 1).'?id='.$object->id;
	$head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath('/dolismq/view/question/question_agenda.php', 1).'?id='.$object->id;
	$head[$h][1] = '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events');
	$head[$h][2] = 'questionAgenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'question@dolismq');

	return $head;
}
