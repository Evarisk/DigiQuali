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
 * \file    core/tpl/modal/modal_task_timespent_list.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal task timespent list
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $task
 */

$taskInfos = get_task_infos($task); ?>

<div class="wpeo-modal modal-answer-task-timespent-list" id="answer_task_timespent_list" data-task-id="<?php echo $task->id ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('TimeSpentList') . ' - ' . $task->getNomUrl(1); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content answer-task-timespent-container">
            <?php if (is_array($taskInfos['task']['timespent']) && !empty($taskInfos['task']['timespent'])) :
                foreach ($taskInfos['task']['timespent'] as $timespent) : ?>
                    <div class="question__action-body" id="answer_task_timespent_view<?php echo $timespent['id']; ?>">
                        <div class="question__action-metas">
                            <span class="question__action-metas-author"><?php echo $timespent['author']; ?></span>
                            <span class="question__action-metas-date"><i class="fas fa-calendar-alt pictofixedwidth"></i><?php echo $timespent['date']; ?></span>
                            <span class="question__action-metas-budget"><i class="fas fa-clock pictofixedwidth"></i><?php echo $timespent['duration']; ?></span>
                        </div>
                        <div class="question__action-content"><?php echo $timespent['comment']; ?></div>
                    </div>
                    <div class="question__action-buttons">
                        <?php if (!empty($permissionToAddTask)) : ?>
                            <div class="wpeo-button button-square-40 button-transparent modal-open">
                                <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_edit" data-from-id="<?php echo $timespent['id']; ?>" data-from-module="<?php echo $object->module; ?>">
                                <i class="fas fa-pencil-alt button-icon"></i>
                            </div>
                        <?php endif; ?>
                        <div class="wpeo-button button-square-40 button-transparent answer-task-timespent-delete" data-message="<?php echo $langs->transnoentities('DeleteTaskTimeSpent') . ' ' . $task->ref; ?>" data-task-timespent-id="<?php echo $timespent['id']; ?>">
                            <i class="fas fa-trash button-icon"></i>
                        </div>
                    </div>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
</div>
