/**
 * Initialise l'objet "sheet" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.digiquali.sheet = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.init = function() {
	window.digiquali.sheet.event();
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.event = function() {

  $( document ).on( 'click', '.toggle-group-in-tree', window.digiquali.sheet.toggleGroupInTree );
  $( document ).on( 'click', '#addQuestionButton, #addGroupButton', window.digiquali.sheet.buttonActions );
};

window.digiquali.sheet.buttonActions = function() {
  const addQuestionRow = $('#addQuestionRow');
  const addGroupRow = $('#addGroupRow');

  if ($(this).attr('id') === 'addQuestionButton') {
    addQuestionRow.removeClass('hidden');
    addGroupRow.addClass('hidden');
  } else {
    addGroupRow.removeClass('hidden');
    addQuestionRow.addClass('hidden');
  }
}


window.digiquali.sheet.toggleGroup = function(groupId) {
  const groupQuestions = $(`.group-question-${groupId}`);
  const toggleIcon = $(`#group-${groupId} .toggle-icon`);

  groupQuestions.each(function () {
    const question = $(this);
    const isHidden = question.toggleClass('hidden').hasClass('hidden');
    toggleIcon.text(isHidden ? '+' : '-');
  });
}


window.digiquali.sheet.closeAllGroups = function () {
  const groupQuestions = $('.group-question');
  const toggleIcons = $('.toggle-icon');

  groupQuestions.addClass('hidden');
  toggleIcons.text('+');
}


window.digiquali.sheet.toggleGroupInTree = function () {
  let subQuestions = $(this).closest('.group-item').next()[0];
  $(this).toggleClass('fa-chevron-up fa-chevron-down');

  if (subQuestions && subQuestions.classList.contains("sub-questions")) {
    subQuestions.classList.toggle("collapsed");
  }
}

window.digiquali.sheet.greyOut = function () {
  const questionGroups = $('.group-item');
  const questions = $('.question-item');
  const sheets = $('.sheet-header');

  questionGroups.removeClass('selected');
  questions.removeClass('selected');
  sheets.removeClass('selected');
}

/**
 * Drag and drop on move-line action
 *
 * @since   20.1.0
 * @version 20.1.0
 *
 * @return {void}
 */
window.digiquali.sheet.draganddrop = function () {
  $(this).css('cursor', 'pointer');

  $('#tablelines tbody').sortable({
    handle: '.move-line',
    items: '> .line-row',
    tolerance: 'intersect',
    stop: function (event, ui) {
      $(this).css('cursor', 'default');

      const movedRow = ui.item;
      const movedRowId = movedRow.attr('id');

      if (movedRow.hasClass('question-group')) {
        const groupId = movedRowId.replace('group-', '');
        const questions = $(`.group-question-${groupId}`);

        questions.each(function () {
          $(this).insertAfter(movedRow);
        });
      }

      let lineOrder = [];
      $('.line-row').each(function () {
        lineOrder.push($(this).attr('id'));
      });

      const token = window.saturne.utils.getToken();

      let separator = window.saturne.utils.getQuerySeparator();

      $.ajax({
        url: document.URL + separator + "action=moveLine&token=" + token,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder,
        }),
        processData: false,
        contentType: 'application/json',
      });
    },
    receive: function (event, ui) {
      const movedRow = ui.item;
      if (movedRow.hasClass('group-question')) {
        const targetGroup = ui.placeholder.closest('.question-group');
        if (targetGroup.length) {
          $(this).sortable('cancel');
        }
      }
    },
  });
};
