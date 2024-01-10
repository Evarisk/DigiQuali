
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
