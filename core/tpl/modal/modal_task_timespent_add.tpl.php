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
 * \file    core/tpl/modal/modal_task_timespent_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal task timespent add
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $task
 */ ?>

<div class="wpeo-modal modal-answer-task-timespent-add" id="answer_task_timespent_add" data-task-id="<?php echo $task->id ?>">
    <div class="modal-container wpeo-modal-event" style="max-width: 400px; max-height: 300px;">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('TimeSpentAdd') . ' - ' . $task->getNomUrl(1); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content answer-task-timespent-container">
            <div class="timespent-comment">
                <label>
                    <span class="title"><?php echo $langs->trans('Comment'); ?></span>
                    <input type="text" id="answer-task-timespent-comment" name="comment">
                </label>
            </div>
            <div class="wpeo-gridlayout grid-2">
                <div>
                    <label>
                        <span class="title"><?php echo $langs->trans('Date'); ?></span>
                        <input type="datetime-local" id="answer-task-timespent-date" name="timespent_date" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M'); ?>">
                    </label>
                </div>
                <div>
                    <label>
                        <span class="title"><?php echo $langs->trans('Duration'); ?></span>
                        <span class="time"><input type="number" id="answer-task-timespent-duration" name="timespentDuration" min="0" value="15"></span>
                    </label>
                </div>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button answer-task-timespent-create modal-close">
                <i class="fas fa-plus "></i>
            </div>
        </div>
    </div>
</div>
