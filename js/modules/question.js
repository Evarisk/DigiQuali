
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
