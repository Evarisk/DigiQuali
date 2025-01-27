
/**
 * Initialise l'objet "question" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiquali.question = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
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
 * La méthode contenant tous les événements pour le question.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiquali.question.event = function() {
	$( document ).on( 'click', '.clicked-photo-preview', window.digiquali.question.previewPhoto );
	$( document ).on( 'click', '.ui-dialog-titlebar-close', window.digiquali.question.closePreviewPhoto );
	$( document ).on( 'click', '#show_photo', window.digiquali.question.showPhoto );
	$( document ).on( 'click', '.answer-picto .item, .wpeo-table .item', window.digiquali.question.selectAnswerPicto );

  $(document).on('change', 'select[data-type="question-type"]', window.digiquali.question.changeQuestionType);
};

/**
 * Add border on preview photo.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.question.previewPhoto = function ( event ) {
	if ($(this).hasClass('photo-ok')) {
		$("#dialogforpopup").attr('style', 'border: 10px solid #47e58e')
	} else if ($(this).hasClass('photo-ko'))  {
		$("#dialogforpopup").attr('style', 'border: 10px solid #e05353')
	}
};

/**
 * Close preview photo.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.question.closePreviewPhoto = function ( event ) {
	$("#dialogforpopup").attr('style', 'border:')
};

/**
 * Show photo for question.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.question.showPhoto = function() {
	let photo = $(this).closest('.question-table').find('.linked-medias')

	if (photo.hasClass('hidden')) {
		photo.attr('style', '')
		photo.removeClass('hidden')
	} else {
		photo.attr('style', 'display:none')
		photo.addClass('hidden')
	}
};

/**
 * Lors du clic sur un picto de réponse, remplace le contenu du toggle et met l'image du picto sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiquali.question.selectAnswerPicto = function( event ) {
	var element = $(this).closest('.wpeo-dropdown');
	$(this).closest('.content').removeClass('active');
	element.find('.dropdown-toggle span').hide();
	element.find('.dropdown-toggle.button-picto').html($(this).closest('.wpeo-tooltip-event').html());
	element.find('.input-hidden-picto').val($(this).data('label'));
};

/**
 * Change question type.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.question.changeQuestionType = function() {
  let questionType = $(this).val();
  if (questionType == 'Percentage') {
    $(document).find('#question-step-count').removeClass('hidden');
  } else {
    $(document).find('#question-step-count').addClass('hidden');
  }
}
