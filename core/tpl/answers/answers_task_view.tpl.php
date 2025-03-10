<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/answers/answers_task_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers task view
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $objectLine
 */ ?>

<div class="question__list-actions" id="question_task_list<?php echo $objectLine->id ?>" data-objectLine-id="<?php echo $objectLine->id ?>" data-objectLine-element="<?php echo $objectLine->element ?>">
    <?php foreach ($objectLine->linkedObjects['project_task'] ?? [] as $task) :
        $taskInfos = get_task_infos($task); ?>
        <div class="question__action" id="answer_task<?php echo $task->id ?>" data-task-id="<?php echo $task->id; ?>">
            <div class="question__action-check">
                <label>
                    <input type="checkbox" <?php echo ($taskInfos['task']['progress'] == 100 ? 'checked' : ''); ?>/>
                </label>
            </div>
            <div class="question__action-body">
                <div class="question__action-metas">
                    <span class="question__action-metas-ref"><?php echo $taskInfos['task']['ref']; ?></span>
                    <span class="question__action-metas-author"><?php echo $taskInfos['task']['author']; ?></span>
                    <span class="question__action-metas-date"><i class="fas fa-calendar-alt pictofixedwidth"></i><?php echo $taskInfos['task']['date']; ?></span>
                    <div class="modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_list" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                        <span class="question__action-metas-time"><i class="fas fa-clock pictofixedwidth"></i><?php echo $taskInfos['task']['time']; ?></span>
                    </div>
                    <div class="modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_add" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                        <i class="fas fa-plus"></i>
                    </div>
                    <span class="question__action-metas-budget"><i class="fas fa-coins pictofixedwidth"></i><?php echo $taskInfos['task']['budget']; ?></span>
                </div>
                <div class="question__action-content"><?php echo $taskInfos['task']['label']; ?></div>
            </div>
            <div class="question__action-buttons">
                <?php if (!empty($permissionToAddTask)) : ?>
                    <div class="wpeo-button button-square-40 button-transparent modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_edit" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                        <i class="fas fa-pencil-alt button-icon"></i>
                    </div>
                <?php endif; ?>
                <div class="wpeo-button button-square-40 button-transparent delete-task" data-message="<?php echo $langs->transnoentities('DeleteTask') . ' ' . $task->ref; ?>" data-task-id="<?php echo $task->id; ?>">
                    <i class="fas fa-trash button-icon"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
