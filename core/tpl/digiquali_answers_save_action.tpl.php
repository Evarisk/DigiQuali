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
 * Global     : $conf, $langs, $user
 * Parameters : $action
 * Objects    : $object, $objectLine, $sheet
 */

if ($action == 'save') {
    $data = json_decode(file_get_contents('php://input'), true);

    $sheet->fetch($object->fk_sheet);
    $sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_' . $sheet->element);
    if (!empty($sheet->linkedObjects['digiquali_question'])) {
        foreach ($sheet->linkedObjects['digiquali_question'] as $question) {
            if (!empty($object->lines)) {
                foreach ($object->lines as $line) {
                    if ($line->fk_question === $question->id) {
                        // Save answer value
                        if ($data['autoSave'] && $question->id == $data['questionId']) {
                            $questionAnswer = $data['answer'];
                        } else {
                            $questionAnswer = GETPOST('answer' . $question->id);
                        }
                        if (!empty($questionAnswer)) {
                            $line->answer = $questionAnswer;
                        }

                        // Save answer comment
                        if ($data['autoSave'] && $question->id == $data['questionId']) {
                            $comment = $data['comment'];
                        } else {
                            $comment = GETPOST('comment' . $question->id);
                        }
                        if (dol_strlen($comment) > 0) {
                            $line->comment = $comment;
                        }

                        $line->update($user);
                    }
                }
            } else {
                $objectLine->ref         = $objectLine->getNextNumRef();
                $fk_element              = 'fk_'. $object->element;
                $objectLine->$fk_element = $object->id;
                $objectLine->fk_question = $question->id;

                // Save answer value
                if ($data['autoSave'] && $question->id == $data['questionId']) {
                    $questionAnswer = $data['answer'];
                } else {
                    $questionAnswer = GETPOST('answer' . $question->id);
                }
                if (!empty($questionAnswer)) {
                    $objectLine->answer = $questionAnswer;
                } else {
                    $objectLine->answer = '';
                }

                // Save answer comment
                if ($data['autoSave'] && $question->id == $data['questionId']) {
                    $comment = $data['comment'];
                } else {
                    $comment = GETPOST('comment' . $question->id);
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
    }

    if (GETPOSTISSET('public_interface') && $sheet->type == 'survey') {
        $object->setLocked($user);
    }

    $object->call_trigger('OBJECT_SAVEANSWER', $user);
    setEventMessages($langs->trans('AnswerSaved'), []);
    header('Location: ' . $_SERVER['PHP_SELF'] . (dol_strlen(GETPOST('track_id')) > 0 ? '?action=saved_success&object_type=' . GETPOST('object_type') : '?id=' . GETPOST('id')));
    exit;
}
