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
    const addQuestionRow = document.getElementById("addQuestionRow");
    const addGroupRow = document.getElementById("addGroupRow");

    if ($(this).attr('id') === 'addQuestionButton') {
      addQuestionRow.classList.remove("hidden");
      addGroupRow.classList.add("hidden");
    } else {
      addGroupRow.classList.remove("hidden");
      addQuestionRow.classList.add("hidden");
    }
}

function toggleGroup(groupId) {
  const groupQuestions = $(`.group-question-${groupId}`);
  const toggleIcon = document.querySelector(`#group-${groupId} .toggle-icon`);


  groupQuestions.each(function () {
    const question = $(this);
    question.toggleClass('hidden');
    const isHidden = question.hasClass('hidden');
    toggleIcon.textContent = isHidden ? '+' : '-';
  });
}

window.digiquali.sheet.closeAllGroups = function () {
  const groupQuestions = document.querySelectorAll('.group-question');

  groupQuestions.forEach(question => {
    question.classList.add('hidden');
  });

  const toggleIcons = document.querySelectorAll('.toggle-icon');

  toggleIcons.forEach(icon => {
    icon.textContent = '+';
  });
}

window.digiquali.sheet.toggleGroupInTree = function () {
  let subQuestions = $(this).closest('.group-item').next()[0];
  $(this).toggleClass('fa-chevron-up fa-chevron-down');

  if (subQuestions && subQuestions.classList.contains("sub-questions")) {
    subQuestions.classList.toggle("collapsed");
  }
}

window.digiquali.sheet.showQuestionGroupCard = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const token = window.saturne.toolbox.getToken()
  const url = $('#questionGroupCardUrl').val() + '?id=' + id;
  $.ajax({
    url: url + '&token=' + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function( resp ) {
      $('#cardContent').html($(resp).find('#cardContent').html());
      $(`.group-item[data-id=${id}]`).addClass('selected');

      window.digiquali.sheet.hookAjaxForms();
      window.digiquali.sheet.hookAjaxLinks();
    }
    },
  );
}

window.digiquali.sheet.showQuestionCard = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const groupId = $(this).data("group-id")
  const token = window.saturne.toolbox.getToken()
  const url = $('#questionCardUrl').val() + '?id=' + id;
  $.ajax({
      url: url + '&token=' + token,
      type: "POST",
      processData: false,
      contentType: false,
      success: function( resp ) {
        $('#cardContent').html($(resp).find('#cardContent').html());
        $(`.question-item[data-id='${id}'][data-group-id='${groupId}']`).addClass('selected');
      }
    },
  );
}

window.digiquali.sheet.showSheet = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const token = window.saturne.toolbox.getToken();
  const url = $('#sheetCardUrl').val() + '?id=' + id;

  $.ajax({
      url: url + '&token=' + token,
      type: "POST",
      processData: false,
      contentType: false,
      success: function(resp) {
        let newContent = $(resp).find('#cardContent').removeClass('margin-for-tree');
        $('#cardContent').children().remove();
        $('#cardContent').append(newContent.children());
        $(`.sheet-header[data-id=${id}]`).addClass('selected');
      }
    }
  );
};

window.digiquali.sheet.greyOut = function () {
  const questionGroups = document.querySelectorAll('.group-item');
  const questions = document.querySelectorAll('.question-item');
  const sheets = document.querySelectorAll('.sheet-header');

  questionGroups.forEach(group => {
    group.classList.remove('selected');
  });

  questions.forEach(question => {
    question.classList.remove('selected');
  });

  sheets.forEach(sheet => {
    sheet.classList.remove('selected');
  });
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

      const token = $('.fiche').find('input[name="token"]').val();

      let separator = '&';
      if (document.URL.match(/action=/)) {
        document.URL = document.URL.split(/\?/)[0];
        separator = '?';
      }

      $.ajax({
        url: document.URL + separator + "action=moveLine&token=" + token,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder,
        }),
        processData: false,
        contentType: 'application/json',
        success: function () {
          console.log('Order successfully updated');
        },
        error: function () {
          console.error('Failed to update order');
        },
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
