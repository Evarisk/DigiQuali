
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
  $( document ).on( 'click', '.answer:not(.disable)', window.digiquali.control.selectAnswer );
  $( document ).on( 'input', '.input-answer:not(.disable)', window.digiquali.control.selectAnswer );
  $( document ).on( 'change', '.control-table.linked-objects select', window.digiquali.control.disableOtherSelectors );
  $( document ).on( 'keyup', '.question-comment', window.digiquali.control.showCommentUnsaved );
  $( document ).on( 'click', '.validateButton', window.digiquali.control.getAnswerCounter);
  $( document ).on( 'change', '#fk_sheet', window.digiquali.control.showSelectObjectLinked);
  $( document ).on( 'click', '.toggleControlInfo', window.digiquali.control.toggleControlInfo );
  $( document ).on( 'click', '.clipboard-copy', window.digiquali.control.copyToClipboard );
  $( document ).on( 'change', '#productId', window.digiquali.control.refreshLotSelector );
  $( document ).on( 'click', '.switch-public-control-view', window.digiquali.control.switchPublicControlView );
  $(document).on('click', '.show-only-questions-with-no-answer', window.digiquali.control.showOnlyQuestionsWithNoAnswer);
};

/**
 * Select an answer for a control question.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.selectAnswer = function ( event ) {
  let questionElement = $(this).closest('.select-answer.answer-cell');
  let questionId      = questionElement.attr('data-questionId');
  let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');
  let autoSave        = $(this).closest('.table-id-' + questionId).attr('data-autoSave');
  let answer          = '';
  let answerValue     = $(this).hasClass('answer') ? $(this).attr('value') : $(this).val();
  let comment         = $(this).closest('.table-id-' + questionId).find('#comment' + questionId).val();
	if ($(this).closest('.table-cell').hasClass('select-answer')) {
		if ($(this).hasClass('multiple-answers')) {
			$(this).closest('span').toggleClass( 'active' );
			let selectedValues = []
			questionElement.find('.multiple-answers.active').each(function() {
				selectedValues.push($(this).attr('value'))
			})
			answer = selectedValues
		} else {
			$(this).closest('.table-cell').find('.answer.active').css( 'background-color', '#fff' );

			$(this).closest('.table-cell').find('span').removeClass( 'active' );
			$(this).closest('span').addClass( 'active' );
			answer = answerValue
		}
		if ($(this).hasClass('active')) {
			let answerColor = $(this).closest('.answer-cell').find('.answer-color-' + $(this).attr('value')).val()
			$(this).attr('style', $(this).attr('style') + ' background:'+answerColor+';')
		} else {
			$(this).attr('style', $(this).attr('style') + ' background:#fff;')
		}
		$(this).closest('.answer-cell').find('.question-answer').val(answer)
	}

  if (!publicInterface && autoSave == 1 && !$(this).hasClass('multiple-answers')) {
    window.digiquali.control.saveAnswer(questionId, answer, comment);
  } else {
    window.digiquali.control.updateButtonsStatus()
  }
};

/**
 * Disable selectors on control object selection.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.disableOtherSelectors = function ( event ) {
	var controlForm = document.getElementById('createControlForm');
	var formData = new FormData(controlForm);

	let selectorId = $(this).attr('id');
	let selectorData = formData.get(selectorId)

	if (selectorData >= 0) {
		$('.control-table.linked-objects').find('select').not('#' + selectorId).attr('disabled', 1);
	} else {
		$('.control-table.linked-objects').find('select').not('#' + selectorId).removeAttr('disabled');
	}
};


/**
 * Show a comment for a control question if focus out.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.showCommentUnsaved = function ( event ) {
	if (!$(this).hasClass('show-comment-unsaved-message')) {
		$(this).after('<p style="color:red">Commentaire non enregistré</p>');
		$(this).addClass('show-comment-unsaved-message');
	}
	window.digiquali.control.updateButtonsStatus()
};

/**
 * Change buttons status
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.updateButtonsStatus = function (  ) {
	$('#saveButton').removeClass('butActionRefused')
	$('#saveButton').addClass('butAction')
  $('#saveButton').css('background', '#0d8aff')
  $('.fa-circle').css('display', 'inline')
	$('#saveButton').attr('onclick','$("#saveControl").submit()');

	$('.validateButton').removeClass('butAction')
	$('#dialog-confirm-actionButtonValidate').removeAttr('id');
	$('.validateButton').addClass('butActionRefused')
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
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.showSelectObjectLinked = function ( event ) {
	var controlForm = document.getElementById('createControlForm');
	var formData = new FormData(controlForm);

	let token = $('.id-container').find('input[name="token"]').val();
	let action = '?action=create'

	let sheetId = formData.get('fk_sheet')
	let userController = formData.get('fk_user_controller')
	let projectId = formData.get('fk_project')
	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&fk_sheet=' + sheetId + '&token=' + token
	urlToGo += '&fk_project=' + projectId
	urlToGo += '&fk_user_controller=' + userController

	window.saturne.loader.display($('.tabBar.tabBarWithBottom tbody'))
	$.ajax({
		url: urlToGo,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.tabBar.tabBarWithBottom tbody').html($(resp).find('.tabBar.tabBarWithBottom tbody').children())
			$('.wpeo-loader').removeClass('wpeo-loader')
		},
		error: function ( ) {
		}
	});
}

/**
 * Show control info if toggle control info is on.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.toggleControlInfo = function ( event ) {
	if ($(this).hasClass('fa-minus-square')) {
		$(this).removeClass('fa-minus-square').addClass('fa-caret-square-down')
		$(this).closest('.fiche').find('.fichecenter.controlInfo').addClass('hidden')
	} else {
		$(this).removeClass('fa-caret-square-down').addClass('fa-minus-square')
		$(this).closest('.fiche').find('.fichecenter.controlInfo').removeClass('hidden')
	}
}

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
      $('.questionLines').replaceWith($(resp).find('.questionLines'))
    },
    error: function() {}
  });
};

/**
 * Save answer after click event
 *
 * @since   1.9.0
 * @version 1.9.0
 *
 * @param  {int}    questionId Question ID
 * @param  {string} answer     Answer value
 * @param  {string} comment    Comment value
 * @return {void}
 */
window.digiquali.control.saveAnswer = function(questionId, answer, comment) {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.table-id-' + questionId));

  $.ajax({
    url: document.URL + querySeparator + 'action=save&token=' + token,
    type: "POST",
    data: JSON.stringify({
      autoSave: true,
      questionId: questionId,
      answer: answer,
      comment: comment
    }),
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.fiche').replaceWith($(resp).find('.fiche'));
    },
    error: function() {}
  });
};
