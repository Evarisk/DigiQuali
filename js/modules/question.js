
/**
 * Initialise l'objet "question" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.dolismq.question = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.dolismq.question.init = function() {
	window.dolismq.question.event();
};

/**
 * La méthode contenant tous les événements pour le question.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.dolismq.question.event = function() {
	$( document ).on( 'click', '.clicked-photo-preview', window.dolismq.question.previewPhoto );
	$( document ).on( 'click', '.ui-dialog-titlebar-close', window.dolismq.question.closePreviewPhoto );
	$( document ).on( 'click', '#show_photo', window.dolismq.question.showPhoto );
	$( document ).on( 'click', '.answer-picto .item, .wpeo-table .item', window.dolismq.question.selectAnswerPicto );
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
window.dolismq.question.previewPhoto = function ( event ) {
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
window.dolismq.question.closePreviewPhoto = function ( event ) {
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
window.dolismq.question.showPhoto = function() {
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
window.dolismq.question.selectAnswerPicto = function( event ) {
	var element = $(this).closest('.wpeo-dropdown');
	$(this).closest('.content').removeClass('active');
	element.find('.dropdown-toggle span').hide();
	element.find('.dropdown-toggle.button-picto').html($(this).closest('.wpeo-tooltip-event').html());
	element.find('.input-hidden-picto').val($(this).data('label'));
};
