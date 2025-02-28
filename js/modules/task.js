/* Copyright (C) 2023-2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/task.js
 * \ingroup digiquali
 * \brief   JavaScript tasks file
 */
'use strict';

/**
 * Init task JS
 *
 * @since   20.2.0
 * @version 20.2.0
 */
window.digiquali.task = {};

/**
 * Task init
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.init = function() {
  window.digiquali.task.event();
};

/**
 * Task event
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.event = function() {

  $(document).on('input', '.answer-task-label', window.digiquali.task.updateTaskModal);

  $(document).on('click', '.answer-task-create:not(.button-disable)', window.digiquali.task.createTask);
  $(document).on('click', '.answer-task-save', window.digiquali.task.updateTask);
  $(document).on('click', '.question__action .delete-task', window.digiquali.task.deleteTask);
  $(document).on('change', '.question__action-check input[type="checkbox"]', window.digiquali.task.checkTask);

  $(document).on('click', '.answer-task-timespent-create', window.digiquali.task.createTimeSpent);
  $(document).on('click', '.answer-task-timespent-delete', window.digiquali.task.deleteTimeSpent);
};

/**
 * Update task modal
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.updateTaskModal = function() {
  const $this = $(this);
  const $modal = $this.closest('.wpeo-modal');
  const $button = $modal.find('.wpeo-button.answer-task-create');
  const value = $this.val();

  if (value.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
  }
}

/**
 * Create task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.createTask = function() {
  const $this  = $(this);
  const $modal = $this.closest('.wpeo-modal');

  const label     = $modal.find('.answer-task-label').val();
  const startDate = $modal.find('.answer-task-start-date').val();
  const endDate   = $modal.find('.answer-task-end-date').val();
  const budget    = $modal.find('.answer-task-budget').val();

  const projectId = $modal.data('project-id') ? $modal.data('project-id') : null;
  const lineId    = $modal.data('line-id') ? $modal.data('line-id') : null;
  const $list     = $(document).find(`#answer_task_list${lineId}`);

  const token  = window.saturne.toolbox.getToken();

  window.saturne.loader.display($list);
  $.ajax({
    url: `${document.URL}&action=add_task&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      label:         label,
      date_start:    startDate,
      date_end:      endDate,
      budget_amount: budget,
      fk_project:    projectId,
      line_id:       lineId
    }),

    success: function(resp) {
      $list.replaceWith($(resp).find(`#answer_task_list${lineId}`));
      const $modals = $('.wpeo-modal');
      $modals.each(function() {
        const $this = $(this);
        $this.replaceWith($(resp).find(`#${$this.attr('id')}`));
      });
    }
  });
};

window.digiquali.task.updateTask = function() {
  const token  = window.saturne.toolbox.getToken();

  const $this  = $(this);
  const $modal = $this.closest('.wpeo-modal');
  const $form  = $modal.find('.answer-task-content');
  const $list  = $(document).find(`#answer_task_list${$this.data('line-id')}`);

  const progress  = $form.find('.answer-task-progress-checkbox').is(':checked') ? 100 : 0;
  const label     = $form.find('.answer-task-label').val();
  const startDate = $form.find('.answer-task-start-date').val();
  const endDate   = $form.find('.answer-task-end-date').val();
  const budget    = parseInt($form.find('.answer-task-budget').val());

  const taskId = $this.data('task-id');

  $modal.removeClass('modal-active');
  window.saturne.loader.display($list);
  $.ajax({
    url: `${document.URL}&action=update_task&task_id=${taskId}&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      task_id:       taskId,
      progress:      progress,
      label:         label,
      date_start:    startDate,
      date_end:      endDate,
      budget_amount: budget
    }),

    success: function(resp) {
      $list.replaceWith($(resp).find(`#answer_task_list${$this.data('line-id')}`));
    }
  });

};

window.digiquali.task.deleteTask = function() {
  const $this   = $(this);
  const $list   = $this.closest('.question__list-actions');
  const taskId  = $this.data('task-id');
  const message = $this.data('message');
  const token   = window.saturne.toolbox.getToken();

  const listId  = $list.attr('id');

  if (!confirm(message)) {
    return;
  }

  window.saturne.loader.display($list);
  $.ajax({
    url: `${document.URL}&action=delete_task&task_id=${taskId}&token=${token}`,
    type: 'POST',
    success: function(resp) {
      $list.replaceWith($(resp).find(`#${listId}`));
    }
  });
}

window.digiquali.task.checkTask = function() {
  const $this  = $(this);
  const taskId = $this.data('task-id');
  const token  = window.saturne.toolbox.getToken();

  $.ajax({
    url: `${document.URL}&action=check_task&task_id=${taskId}&token=${token}`,
    type: 'POST',
    success: function(resp) {}
  });
};

window.digiquali.task.createTimeSpent = function() {
  const $this = $(this);
  const $modal = $this.closest('.wpeo-modal');

  const date      = $modal.find('.answer-task-timespent-date').val();
  const hour      = $modal.find('.answer-task-timespent-datehour').val();
  const minute    = $modal.find('.answer-task-timespent-datemin').val();
  const comment   = $modal.find('.answer-task-timespent-comment').val();
  const timeSpent = $modal.find('.answer-task-timespent-duration').val();

  const taskId = $this.data('task-id');

  const token = window.saturne.toolbox.getToken();

  const $list = $(document).find(`#answer-task-timespent-list${taskId}`);
  window.saturne.loader.display($list);
  $.ajax({
    url: `${document.URL}&action=add_task_timespend&task_id=${taskId}&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      date:       date,
      hour:       hour,
      minute:     minute,
      comment:    comment,
      time_spent: timeSpent
    }),

    success: function(resp) {
      $list.replaceWith($(resp).find(`#answer-task-timespent-list${taskId}`));
      $modal.find(`#task-data${taskId}`).replaceWith($(resp).find(`#task-data${taskId}`));
    }
  })
};

window.digiquali.task.deleteTimeSpent = function() {
  const $this = $(this);
  const $modal = $this.closest('.wpeo-modal');
  const timeSpentId = $this.data('timespent-id');

  const taskId = $modal.data('task-id');
  const $list = $modal.find(`#answer-task-timespent-list${taskId}`);
  const token = window.saturne.toolbox.getToken();

  window.saturne.loader.display($list);
  $.ajax({
    url: `${document.URL}&action=delete_task_timespent&timespent_id=${timeSpentId}&token=${token}`,
    type: 'POST',
    success: function(resp) {
      $list.replaceWith($(resp).find(`#answer-task-timespent-list${taskId}`));
      $(document).find(`#task-data${taskId}`).replaceWith($(resp).find(`#task-data${taskId}`));
    }
  });
};
