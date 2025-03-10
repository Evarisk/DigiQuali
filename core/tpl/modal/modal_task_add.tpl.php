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
 * \file    core/tpl/modal/modal_task_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal task add
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Objects  : $object
 * Variable : $taskNextValue
 */ ?>

<div class="wpeo-modal modal-answer-task-add" id="answer_task_add" data-project-id="<?php echo $object->project->id; ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('TaskCreate') . ' ' . $taskNextValue . ' ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $object->project->getNomUrl(); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content">
            <div class="answer-task-container">
                <div class="answer-task">
                    <label>
                        <span class="title"><?php echo $langs->trans('Label'); ?></span>
                        <input type="text" id="answer-task-label" name="label">
                    </label>
                    <div class="answer-task-date wpeo-gridlayout grid-3">
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                                <input type="datetime-local" id="answer-task-start-date" name="date_start" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M'); ?>">
                            <label>
                        </div>
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                                <input type="datetime-local" id="answer-task-end-date" name="date_end">
                            <label>
                        </div>
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                                <input type="number" id="answer-task-budget" name="budget" min="0">
                            <label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button answer-task-create button-disable modal-close">
                <i class="fas fa-plus pictofixedwidth"></i><?php echo $langs->trans('Add'); ?>
            </div>
        </div>
    </div>
</div>
