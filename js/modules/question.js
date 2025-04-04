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
 * \file    js/modules/question.js
 * \ingroup digiquali
 * \brief   JavaScript question file
 */

'use strict';

/**
 * Init question JS
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiquali.question = {};

/**
 * Question init
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiquali.question.init = function() {
  window.digiquali.question.event();
};

/**
 * Question event
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiquali.question.event = function() {
  $(document).on('click', '.clicked-photo-preview', window.digiquali.question.previewPhoto);
  $(document).on('click', '.ui-dialog-titlebar-close', window.digiquali.question.closePreviewPhoto);
  $(document).on('click', '#show_photo', window.digiquali.question.showPhoto);
  $(document).on('click', '.answer-picto .item, .wpeo-table .item', window.digiquali.question.selectAnswerPicto);

  $(document).on('change', 'select[data-type="question-type"]', window.digiquali.question.changeQuestionType);
};

/**
 * Add border on preview photo
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiquali.question.previewPhoto = function () {
  if ($(this).hasClass('photo-ok')) {
    $("#dialogforpopup").attr('style', 'border: 10px solid #47e58e');
  } else if ($(this).hasClass('photo-ko'))  {
    $("#dialogforpopup").attr('style', 'border: 10px solid #e05353');
  }
};

/**
 * Close preview photo
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiquali.question.closePreviewPhoto = function () {
  $("#dialogforpopup").attr('style', 'border:');
};

/**
 * Displays the linked media (photo) for a question when the checkbox is checked
 *
 * @since   1.3.0
 * @version 20.1.0
 *
 * @returns {void} No return value
 */
window.digiquali.question.showPhoto = function() {
  let checkbox = $(this);
  let photo    = checkbox.closest('.question-table').find('.linked-medias');

  photo.toggleClass('hidden', !checkbox.prop('checked'));
};

/**
 * Lors du clic sur un picto de réponse, remplace le contenu du toggle et met l'image du picto sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiquali.question.selectAnswerPicto = function() {
  const element = $(this).closest('.wpeo-dropdown');
  $(this).closest('.content').removeClass('active');
  element.find('.dropdown-toggle span').hide();
  element.find('.dropdown-toggle.button-picto').html($(this).closest('.wpeo-tooltip-event').html());
  element.find('.input-hidden-picto').val($(this).data('label'));
};

/**
 * Change question type
 *
 * @since   20.1.0
 * @version 20.1.0
 *
 * @return {void}
 */
window.digiquali.question.changeQuestionType = function() {
  let questionType = $(this).val();
  
  if (questionType === 'Percentage') {
    $(document).find('#percentage-question-step').removeClass('hidden');
    $(document).find('#percentage-question-step input').prop('disabled', false);
  } else {
    $(document).find('#percentage-question-step').addClass('hidden');
    $(document).find('#percentage-question-step input').prop('disabled', true);
  }

  if (questionType === 'RangeOfValue') {
    $(document).find('#range-of-values-maximum').removeClass('hidden');
    $(document).find('#range-of-values-maximum input').prop('disabled', false);
    $(document).find('#range-of-values-minimum').removeClass('hidden');
    $(document).find('#range-of-values-minimum input').prop('disabled', false);
    $(document).find('#range-of-values-unit').removeClass('hidden');
    $(document).find('#range-of-values-unit input').prop('disabled', false);
  } else {
    $(document).find('#range-of-values-maximum').addClass('hidden');
    $(document).find('#range-of-values-maximum input').prop('disabled', true);
    $(document).find('#range-of-values-minimum').addClass('hidden');
    $(document).find('#range-of-values-minimum input').prop('disabled', true);
    $(document).find('#range-of-values-unit').addClass('hidden');
    $(document).find('#range-of-values-unit input').prop('disabled', true);
  }
};
