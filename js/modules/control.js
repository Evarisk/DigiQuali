
/**
 * Initialise l'objet "control" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiquali.control = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiquali.control.init = function() {
	window.digiquali.control.event();
};

/**
 * La méthode contenant tous les événements pour le control.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiquali.control.event = function() {
  $( document ).on( 'click', '.validateButton', window.digiquali.control.getAnswerCounter);
  $( document ).on( 'change', '#fk_sheet', window.digiquali.control.showSelectObjectLinked);
  $( document ).on( 'click', '.clipboard-copy', window.digiquali.control.copyToClipboard );
  $( document ).on( 'change', '#productId', window.digiquali.control.refreshLotSelector );
  $( document ).on( 'click', '.switch-public-control-view', window.digiquali.control.switchPublicControlView );
  $(document).on('click', '.show-only-questions-with-no-answer', window.digiquali.control.showOnlyQuestionsWithNoAnswer);
  $(document).on('click', '.photo-sheet-category', window.digiquali.control.getSheetCategoryID);
  $(document).on('click', '.photo-sheet-sub-category', window.digiquali.control.getSheetSubCategoryID);
  $(document).on('click', '.photo-sheet', window.digiquali.control.getSheetID);
  $(document).on('click', '.saveSubControl', window.digiquali.control.saveSubControl);
  $(document).on('click', '.lockSubControl', window.digiquali.control.lockSubControl);

};

/**
 * Get answered questions counter
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.getAnswerCounter = function ( event ) {
	let answerCounter = 0
	jQuery("#tablelines").children().each(function() {
		if ($(this).find(".answer.active").length > 0) {
			answerCounter += 1;
		}
	})
	document.cookie = "answerCounter=" + answerCounter
}

/**
 * Show select objects depending on sheet controllable objects
 *
 * @since   1.0.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.showSelectObjectLinked = function() {
  let sheetID        = $(this).val();
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let url = document.URL + querySeparator + 'fk_sheet=' + sheetID + '&token=' + token;

  window.saturne.loader.display($('.linked-objects'));

  $.ajax({
    url: url,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Copy current link to clipboard
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.copyToClipboard = function(  event ) {
	let copyText = $(".copy-to-clipboard").attr('value')
	navigator.clipboard.writeText(copyText).then(() => {
			$('.clipboard-copy').animate({
				backgroundColor: "#59ed9c"
			}, 200, () => {
				$('.clipboard-copy').attr('class', 'fas fa-check  clipboard-copy')
				$(this).tooltip({items : '.clipboard-copy', content: $('#copyToClipboardTooltip').val()});
				$(this).tooltip("open");
				$('.clipboard-copy').attr('style', '')
			})
		}
	)
};

/**
 * Refresh product lot selector
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.refreshLotSelector = function(  event ) {

  var controlEquipmentForm = document.getElementById('add_control_equipment');
  var formData = new FormData(controlEquipmentForm);

  let token = window.saturne.toolbox.getToken();

  let productId = formData.get('productId')
  let urlToGo = document.URL + '&token=' + token
  urlToGo += '&fk_product=' + productId
  window.saturne.loader.display($('.product-lot'))
  $.ajax({
    url: urlToGo,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      $('.product-lot').replaceWith($(resp).find('.product-lot'))
    },
    error: function ( ) {
    }
  });
};

/**
 * Switch public control history mode
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.switchPublicControlView = function(  event ) {

  var publicControlViewMode = $(this).find('.public-control-view').val()
  let token                 = window.saturne.toolbox.getToken();
  let urlToGo               = document.URL + '&token=' + token

  if (publicControlViewMode == 0) {
    urlToGo += '&show_control_list=1'
  } else {
    urlToGo += '&show_last_control=1'
  }

  window.saturne.loader.display($('.signature-container'))

  $.ajax({
    url: urlToGo,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      $('#publicControlHistory').replaceWith($(resp).find('#publicControlHistory'))
    },
    error: function ( ) {
    }
  });
};

/**
 * Enables/disables the configuration to display only questions with no answer
 *
 * @memberof DigiQuali_Control
 *
 * @since   1.9.0
 * @version 1.9.0
 *
 * @return {void}
 */
window.digiquali.control.showOnlyQuestionsWithNoAnswer = function() {
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let token          = window.saturne.toolbox.getToken();

  let showOnlyQuestionsWithNoAnswer;
  if ($(this).hasClass('fa-toggle-off')) {
    showOnlyQuestionsWithNoAnswer = 1;
  } else {
    showOnlyQuestionsWithNoAnswer = 0;
  }

  window.saturne.loader.display($(this));

  $.ajax({
    url: document.URL + querySeparator + "action=show_only_questions_with_no_answer&token=" + token,
    type: "POST",
    processData: false,
    data: JSON.stringify({
      showOnlyQuestionsWithNoAnswer: showOnlyQuestionsWithNoAnswer
    }),
    contentType: false,
    success: function(resp) {
      $('.progress-info').replaceWith($(resp).find('.progress-info'));
      $('.question-answer-container').replaceWith($(resp).find('.question-answer-container'));
    },
    error: function() {}
  });
};

/**
 * Get sheet category ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetCategoryID = function() {
  let sheetCategoryID = $(this).attr('value');
  let token           = window.saturne.toolbox.getToken();
  let querySeparator  = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.sheet-images-container'));

  $.ajax({
    url: document.URL + querySeparator + 'sheetCategoryID=' + sheetCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-images-container').replaceWith($(resp).find('.sheet-images-container'));
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').addClass('photo-sheet-category-active');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Get sheet sub category ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetSubCategoryID = function() {
  let sheetCategoryID    = $('.photo-sheet-category-active').attr('value');
  let sheetSubCategoryID = $(this).attr('value');
  let token              = window.saturne.toolbox.getToken();
  let querySeparator     = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.sheet-images-container'));

  $.ajax({
    url: document.URL + querySeparator + 'sheetCategoryID=' + sheetCategoryID + '&sheetSubCategoryID=' + sheetSubCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-images-container').replaceWith($(resp).find('.sheet-images-container'));
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').addClass('photo-sheet-category-active');
      $('.photo-sheet-sub-category[value=' + sheetSubCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-sub-category[value=' + sheetSubCategoryID + ']').addClass('photo-sheet-sub-category-active');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Get sheet ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetID = function() {
  let sheetID            = $(this).attr('data-object-id');
  let sheetCategoryID    = $('.photo-sheet-category-active').attr('value');
  let sheetSubCategoryID = $('.photo-sheet-sub-category-active').attr('value');
  let token              = window.saturne.toolbox.getToken();
  let querySeparator     = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display($('.sheet-elements'));
  window.saturne.loader.display($('.linked-objects'));

  $.ajax({
    url: document.URL + querySeparator + 'fk_sheet=' + sheetID + '&sheetCategoryID=' + sheetCategoryID + '&sheetSubCategoryID=' + sheetSubCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-elements').replaceWith($(resp).find('.sheet-elements'));
      $('.photo-sheet[data-object-id=' + sheetID + ']').css('border', '3px solid #0d8aff');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Save sub control
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.saveSubControl = function() {
  let subControlID = $(this).attr('data-control-id');
  let massControlId = $(this).attr('data-mass-control-id');
  let notePublic = $(this).closest('.table-row').find('.note-public').val();
  // get input checked
  let questionIds = [];
  $(this).closest('.table-row').find('.select-answer').each(function() {
    let questionId = $(this).attr('data-questionid');

    let answer = $(this).find('.question-answer').val();
    let comment = $(this).closest('.wpeo-table').find('.question-comment').val();

    questionIds[questionId] = {
      'answer': answer,
      'comment': comment
    };
  });

  let verdict = $(this).closest('.table-row').find('.verdict-option input[type="radio"]:checked').val();
  let token   = window.saturne.toolbox.getToken();

  window.saturne.loader.display($(this).closest('.table-row'));
  let url = document.URL.replace(/id=\d+/, 'id=' + subControlID);
  $.ajax({
    url: url + '&token=' + token + '&action=confirm_setVerdict&verdict=' + verdict + '&noteControl=' + notePublic,
    type: 'POST',
    processData: false,
    contentType: false,
    data: [],
    success: async function(resp) {
      for (const [questionId, answer] of Object.entries(questionIds)) {
        let url = document.URL.replace(/id=\d+/, 'id=' + subControlID);
        await window.digiquali.object.saveAnswer(questionId, answer.answer, answer.comment, url + '&action=save&token=' + token);
      }

      let url = document.URL.replace(/id=\d+/, 'id=' + massControlId);
      $.ajax({
        url: url,
        type: 'GET',
        processData: false,
        contentType: false,
        data: [],
        success: function (resp) {
          $('.sub-control-' + subControlID).replaceWith($(resp).find('.sub-control-' + subControlID));
        }
      });
    },
    error: function() {}
  });
}

/**
 * Lock sub control
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.lockSubControl = function() {
  let subControlID = $(this).attr('data-control-id');
  let massControlId = $(this).attr('data-mass-control-id');
  let token = window.saturne.toolbox.getToken();

  window.saturne.loader.display($(this).closest('.table-row'));

  let url = document.URL.replace(/id=\d+/, 'id=' + subControlID);
  $.ajax({
    url: url + '&token=' + token + '&action=confirm_lock',
    type: 'POST',
    processData: false,
    contentType: false,
    data: [],
    success: function (resp) {
      let url = document.URL.replace(/id=\d+/, 'id=' + massControlId);
      $.ajax({
        url: url,
        type: 'GET',
        processData: false,
        contentType: false,
        data: [],
        success: function (resp) {
          $('.sub-control-' + subControlID).replaceWith($(resp).find('.sub-control-' + subControlID));
        }
      });
    },
    error: function () {
    }
  });
}
