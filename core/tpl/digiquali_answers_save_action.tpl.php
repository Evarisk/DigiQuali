<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiquali_answers_sava_action.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers save action
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $action
 * Objects    : $object, $objectLine, $sheet
 */

if ($action == 'save') {
    $data = json_decode(file_get_contents('php://input'), true);

    $sheet->fetch($object->fk_sheet);
    $object->fetchObjectLinked($sheet->id, 'digiquali_sheet', '', '', 'OR', 1, 'sourcetype', 0);
    $questionIds = $object->linkedObjectsIds['digiquali_question'];
    foreach ($questionIds as $questionId) {
        $objectLineTmp = $objectLine->fetchFromParentWithQuestion($object->id, $questionId);
        if (is_array($objectLineTmp) && !empty($objectLineTmp)) {
            $objectLineTmp = array_shift($objectLineTmp);

            // Save answer value
            if ($data['autoSave'] && $questionId == $data['questionId']) {
                $questionAnswer = $data['answer'];
            } else {
                $questionAnswer = GETPOST('answer' . $questionId);
            }
            if (!empty($questionAnswer)) {
                $objectLineTmp->answer = $questionAnswer;
            }

            // Save answer comment
            if ($data['autoSave'] && $questionId == $data['questionId']) {
                $comment = $data['comment'];
            } else {
                $comment = GETPOST('comment' . $questionId);
            }
            if (dol_strlen($comment) > 0) {
                $objectLineTmp->comment = $comment;
            } else {
                $objectLine->comment = '';
            }

            $objectLineTmp->update($user);
        } else {
            $objectLine->ref = $objectLine->getNextNumRef();
            $fk_element = 'fk_'. $object->element;
            $objectLine->$fk_element = $object->id;
            $objectLine->fk_question = $questionId;

            // Save answer value
            if ($data['autoSave'] && $questionId == $data['questionId']) {
                $questionAnswer = $data['answer'];
            } else {
                $questionAnswer = GETPOST('answer' . $questionId);
            }
            if (!empty($questionAnswer)) {
                $objectLine->answer = $questionAnswer;
            }

            // Save answer comment
            if ($data['autoSave'] && $questionId == $data['questionId']) {
                $comment = $data['comment'];
            } else {
                $comment = GETPOST('comment' . $questionId);
            }
            if (dol_strlen($comment) > 0) {
                $objectLine->comment = $comment;
            } else {
                $objectLine->comment = '';
            }

            $objectLine->entity = $conf->entity;
            $objectLine->status = 1;

            $objectLine->create($user);
        }
    }

    $object->call_trigger('OBJECT_SAVEANSWER', $user);
    setEventMessages($langs->trans('AnswerSaved'), []);
    header('Location: ' . $_SERVER['PHP_SELF'] . (dol_strlen(GETPOST('track_id')) > 0 ? '?action=saved_success&object_type=' . GETPOST('object_type') : '?id=' . GETPOST('id')));
    exit;
}
