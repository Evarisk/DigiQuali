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
function question_prepare_head(Question $object): array
{
	// Global variables definitions
	global $conf, $db, $langs, $user;

	// Load translation files required by the page
	saturne_load_langs();

	// Initialize values
	$h = 0;
	$head = [];

	$head[$h][0] = dol_buildpath('/dolismq/view/question/question_card.php', 1).'?id='.$object->id;
	$head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Card');
	$head[$h][2] = 'questionCard';
	$h++;


	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/saturne/view/saturne_note.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
		$head[$h][1] = '<i class="fas fa-comment pictofixedwidth"></i>' . $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->dolismq->dir_output. '/audit/' .dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath('/saturne/view/saturne_document.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
	$head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element;
	$head[$h][1] = '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events');
	if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
		$nbEvent = 0;
		// Enable caching of session count actioncomm
		require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
		$cachekey = 'count_events_session_' . $object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = 'SELECT COUNT(id) as nb';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
			$sql .= ' WHERE fk_element = ' . ((int) $object->id);
			$sql .=  " AND elementtype = '" . $object->element . '@dolismq' . "'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm ' . $db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120); // If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans('Agenda');
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbEvent . '</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'question@dolismq');

	return $head;
}
