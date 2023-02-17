/* Javascript library of module Saturne */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <dev@evarisk.com>
 * @copyright 2015-2023 Evarisk
 */

if ( ! window.dolismq ) {
	/**
	 * [dolismq description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Object}
	 */
	window.dolismq = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.dolismq.scriptsLoaded = false;
}

if ( ! window.dolismq.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolismq.init = function() {
		window.dolismq.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolismq.load_list_script = function() {
		if ( ! window.dolismq.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.dolismq ) {

				if ( window.dolismq[key].init ) {
					window.dolismq[key].init();
				}

				for ( slug in window.dolismq[key] ) {

					if ( window.dolismq[key] && window.dolismq[key][slug] && window.dolismq[key][slug].init ) {
						window.dolismq[key][slug].init();
					}

				}
			}

			window.dolismq.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolismq.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.dolismq ) {
			if ( window.dolismq[key].refresh ) {
				window.dolismq[key].refresh();
			}

			for ( slug in window.dolismq[key] ) {

				if ( window.dolismq[key] && window.dolismq[key][slug] && window.dolismq[key][slug].refresh ) {
					window.dolismq[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.dolismq.init );
}


/**
 * Initialise l'objet "control" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.dolismq.control = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.dolismq.control.init = function() {
	window.dolismq.control.event();
};

/**
 * La méthode contenant tous les événements pour le control.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.dolismq.control.event = function() {
	$( document ).on( 'click', '.answer:not(.disable)', window.dolismq.control.selectAnswer );
	$( document ).on( 'keyup', '.question-comment', window.dolismq.control.writeComment );
	$( document ).on( 'keyup', '.question-comment', window.dolismq.control.showCommentUnsaved );
	$( document ).on( 'change', '#fk_product', window.dolismq.control.reloadProductLot );
	$( document ).on( 'change', '#fk_project', window.dolismq.control.reloadTask );
	$( document ).on( 'change', '#fk_soc', window.dolismq.control.reloadContact );
	$( document ).on( 'click', '.validateButton', window.dolismq.control.getAnswerCounter);
	$( document ).on( 'change', '#fk_sheet', window.dolismq.control.showSelectObjectLinked);
	$( document ).on( 'click', '.toggleControlInfo', window.dolismq.control.toggleControlInfo );
	//$( document ).on( 'click', '#select_all_answer', window.dolismq.control.selectAllAnswer);
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
window.dolismq.control.selectAnswer = function ( event ) {
	$(this).closest('.table-cell').find('span').removeClass( 'active' );
	$(this).closest('span').addClass( 'active' );
	$(this).closest('.table-cell').find('.question-answer').val($(this).attr('value'))

	let postName = $(this).closest('.table-cell').find('.question-answer').attr('name')
	let postValue = $(this).closest('.table-cell').find('.question-answer').val()
	//let actualSavePost = $(this).closest('.tabBar').find('.saveButton').attr('href')
	let actualValidatePost = $(this).closest('.tabBar').find('.validateButton').attr('href')
	//$(this).closest('.tabBar').find('.saveButton').attr('href', actualSavePost + '&' + postName + '=' + postValue)
	$(this).closest('.tabBar').find('.validateButton').attr('href', actualValidatePost + '&' + postName + '=' + postValue)
	window.dolismq.control.updateButtonsStatus()
};

/**
 * Write a comment for a control question.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.dolismq.control.writeComment = function ( event ) {

	let postName = $(this).closest('.table-cell').find('.question-comment').attr('name')
	let postValue = $(this).closest('.table-cell').find('.question-comment').val()
	//let actualSavePost = $(this).closest('.tabBar').find('.saveButton').attr('href')
	let actualValidatePost = $(this).closest('.tabBar').find('.validateButton').attr('href')

	//if (actualSavePost.match('&' + postName + '=')) {
	//	actualSavePost = actualSavePost.split('&' + postName + '=')[0]
	//}
	if (actualValidatePost.match('&' + postName + '=')) {
		actualValidatePost = actualValidatePost.split('&' + postName + '=')[0]
	}

	//$(this).closest('.tabBar').find('.saveButton').attr('href', actualSavePost + '&' + postName + '=' + postValue)
	$(this).closest('.tabBar').find('.validateButton').attr('href', actualValidatePost + '&' + postName + '=' + postValue)
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
window.dolismq.control.showCommentUnsaved = function ( event ) {
	if (!$(this).hasClass('show-comment-unsaved-message')) {
		$(this).after('<p style="color:red">Commentaire non enregistré</p>');
		$(this).addClass('show-comment-unsaved-message');
	}
	window.dolismq.control.updateButtonsStatus()
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
window.dolismq.control.updateButtonsStatus = function (  ) {
	$('#saveButton').removeClass('butActionRefused')
	$('#saveButton').addClass('butAction')

	$('#validateButton').removeClass('butAction')
	$('#validateButton').addClass('butActionRefused')
};

/**
 * Write a comment for a control question.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.dolismq.control.reloadProductLot = function ( event ) {
	let token = $('.id-container').find('input[name="token"]').val();
	let action = '?action=create'

	var controlForm = document.getElementById('createControlForm');
	var formData = new FormData(controlForm);

	let sheetId = formData.get('fk_sheet')
	let productId = formData.get('fk_product')

	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&token=' + token + '&fk_sheet=' + sheetId + '&fk_product=' + productId
	$.ajax({
		url: urlToGo,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			console.log($(resp).find('.lot-content'))
			$('.lot-container').html($(resp).find('.lot-content'))
		},
		error: function ( ) {
		}
	});
	//$(this).closest('.control-table').find('.lot-container').load(document.URL+'&productRef='+productRef + ' .lot-content')
};

/**
 * Write a comment for a control question.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.dolismq.control.reloadTask = function ( event ) {

	var controlForm = document.getElementById('createControlForm');
	var formData = new FormData(controlForm);

	let sheetId = formData.get('fk_sheet')
	let projectId = formData.get('fk_project')

	let token = $('.id-container').find('input[name="token"]').val();
	let action = '?action=create'
	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&token=' + token + '&fk_sheet=' + sheetId + '&fk_project=' + projectId

	$.ajax({
		url: urlToGo,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.task-container').html($(resp).find('.task-content'))
		},
		error: function ( ) {
		}
	});
};

/**
 * Reload contact selector after company selection.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.dolismq.control.reloadContact = function ( event ) {

	var controlForm = document.getElementById('createControlForm');
	var formData = new FormData(controlForm);

	let socId = formData.get('fk_soc')
	let sheetId = formData.get('fk_sheet')

	let token = $('.id-container').find('input[name="token"]').val();
	let action = '?action=create'
	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&token=' + token + '&fk_sheet=' + sheetId + '&fk_soc=' + socId

	$.ajax({
		url: urlToGo,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('#fk_contact').html($(resp).find('#fk_contact').children())
		},
		error: function ( ) {
		}
	});
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
window.dolismq.control.getAnswerCounter = function ( event ) {
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
window.dolismq.control.showSelectObjectLinked = function ( event ) {
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
	console.log(urlToGo)
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
window.dolismq.control.toggleControlInfo = function ( event ) {
	if ($(this).hasClass('fa-minus-square')) {
		$(this).removeClass('fa-minus-square').addClass('fa-caret-square-down')
		$(this).closest('.fiche').find('.fichecenter.controlInfo').addClass('hidden')
	} else {
		$(this).removeClass('fa-caret-square-down').addClass('fa-minus-square')
		$(this).closest('.fiche').find('.fichecenter.controlInfo').removeClass('hidden')
	}
}



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

/**
 * Initialise l'objet "sheet" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.dolismq.sheet = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolismq.sheet.init = function() {
	window.dolismq.sheet.event();
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolismq.sheet.event = function() {
};
