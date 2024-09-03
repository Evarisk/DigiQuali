/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/object.js
 * \ingroup digiquali
 * \brief   JavaScript object file for module DigiQuali
 */

/**
 * Init object JS
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @type {Object}
 */
window.digiquali.object = {};

/**
 * Object init
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @returns {void}
 */
window.digiquali.object.init = function() {
  window.digiquali.object.event();
};

/**
 * Object event
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @returns {void}
 */
window.digiquali.object.event = function() {
  $(document).on( 'change', '.object-table.linked-objects select', window.digiquali.object.disableOtherSelectors);
  $(document).on( 'click', '.answer:not(.disable)', window.digiquali.object.selectAnswer);
  $(document).on( 'input', '.input-answer:not(.disable)', window.digiquali.object.selectAnswer);
  $(document).on( 'keyup', '.question-comment', window.digiquali.object.showCommentUnsaved);
  $(document).on( 'click', '.verdict-box', window.digiquali.object.updateButtonsStatus);
};

/**
 * Disable selectors on object selection
 *
 * @since   1.8.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.disableOtherSelectors = function() {
  var objectForm = document.getElementById('createObjectForm');
  var formData   = new FormData(objectForm);

  let selectorId   = $(this).attr('id');
  let selectorData = formData.get(selectorId);

  if (selectorData >= 0) {
    $('.object-table.linked-objects').find('select').not('#' + selectorId).attr('disabled', 1);
  } else {
    $('.object-table.linked-objects').find('select').not('#' + selectorId).removeAttr('disabled');
  }
};

/**
 * Select an answer on question
 *
 * @since   1.0.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.selectAnswer = function() {
  let questionElement = $(this).closest('.select-answer.answer-cell');
  let questionId      = questionElement.attr('data-questionId');
  let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');
  let autoSave        = $(this).closest('.table-id-' + questionId).attr('data-autoSave');
  let answer          = '';
  let answerValue     = $(this).hasClass('answer') ? $(this).attr('value') : $(this).val();
  let comment         = $(this).closest('.table-id-' + questionId).find('#comment' + questionId).val();
  let controlId       = $(this).closest('.table-id-' + questionId).attr('data-control-id');
  if ($(this).closest('.table-cell').hasClass('select-answer')) {
    if ($(this).hasClass('multiple-answers')) {
      $(this).closest('span').toggleClass('active');
      let selectedValues = [];
      questionElement.find('.multiple-answers.active').each(function() {
        selectedValues.push($(this).attr('value'));
      });
      answer = selectedValues;
    } else {
      $(this).closest('.table-cell').find('.answer.active').css( 'background-color', '#fff' );

      $(this).closest('.table-cell').find('span').removeClass('active');
      $(this).closest('span').addClass('active');
      answer = answerValue;
    }
    if ($(this).hasClass('active')) {
      let answerColor = $(this).closest('.answer-cell').find('.answer-color-' + $(this).attr('value')).val();
      $(this).attr('style', $(this).attr('style') + ' background:' + answerColor + ';');
    } else {
      $(this).attr('style', $(this).attr('style') + ' background:#fff;');
    }
    $(this).closest('.answer-cell').find('.question-answer').val(answer);
  }

  if (!publicInterface && autoSave == 1 && !$(this).hasClass('multiple-answers')) {
    window.digiquali.object.saveAnswer(questionId, answer, comment);
  } else {
    window.digiquali.object.updateButtonsStatus(controlId);
  }
};

/**
 * Show a comment for a question answer if focus out
 *
 * @since   1.1.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.showCommentUnsaved = function() {
  if (!$(this).hasClass('show-comment-unsaved-message')) {
    $(this).after('<p style="color:red;">Commentaire non enregistré</p>');
    $(this).addClass('show-comment-unsaved-message');
  }
  window.digiquali.object.updateButtonsStatus();
};

/**
 * Change buttons status
 *
 * @since   1.1.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.updateButtonsStatus = function(controlId) {
  controlId = stringIsInteger(controlId) ? controlId : $(this).attr('data-control-id');
  $('#saveButton' + controlId).removeClass('butActionRefused');
  $('#saveButton' + controlId).addClass('butAction');
  $('.fa-circle').css('display', 'inline');
  $('#saveButton' + controlId).attr('onclick','$("#saveObject'+controlId+'").submit()');

  $('.validateButton' + controlId).removeClass('butAction');
  $('#dialog-confirm-actionButtonValidate' + controlId).removeAttr('id');
  $('.validateButton' + controlId).addClass('butActionRefused');
};

/**
 * Save answer after click event
 *
 * @since   1.9.0
 * @version 1.11.0
 *
 * @param  {int}    questionId Question ID
 * @param  {string} answer     Answer value
 * @param  {string} comment    Comment value
 * @param  {string} customUrl  URL to save answer
 * @return {void}
 */
window.digiquali.object.saveAnswer = function(questionId, answer, comment, customUrl = '') {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let url = customUrl ? customUrl : document.URL + querySeparator + 'action=save&token=' + token
  window.saturne.loader.display($('.table-id-' + questionId));

  $.ajax({
    url: url,
    type: 'POST',
    data: JSON.stringify({
      autoSave: true,
      questionId: questionId,
      answer: answer,
      comment: comment
    }),
    processData: false,
    contentType: false,
    success: function(resp) {
      if (customUrl.length < 1) {
        $('.fiche').replaceWith($(resp).find('.fiche'));
      }
    },
    error: function() {}
  });
};
