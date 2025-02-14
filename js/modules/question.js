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
 * Show photo for question
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.question.showPhoto = function() {
  let photo = $(this).closest('.question-table').find('.linked-medias');

  if (photo.hasClass('hidden')) {
    photo.attr('style', '');
    photo.removeClass('hidden');
  } else {
    photo.attr('style', 'display:none');
    photo.addClass('hidden');
  }
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
  } else {
    $(document).find('#percentage-question-step').addClass('hidden');
  }
};
