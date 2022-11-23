/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \file    dolismq/js/dolismq.js.php
 * \ingroup dolismq
 * \brief	JavaScript file for module DoliSMQ.
 */

/* Javascript library of module DoliSMQ */

/**
 * @namespace EO_Framework_Init
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2021 Eoxia
 */

'use strict';

if ( ! window.eoxiaJS ) {
	/**
	 * [eoxiaJS description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Object}
	 */
	window.eoxiaJS = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.eoxiaJS.scriptsLoaded = false;
}

if ( ! window.eoxiaJS.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.init = function() {
		window.eoxiaJS.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.load_list_script = function() {
		if ( ! window.eoxiaJS.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.eoxiaJS ) {

				if ( window.eoxiaJS[key].init ) {
					window.eoxiaJS[key].init();
				}

				for ( slug in window.eoxiaJS[key] ) {

					if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].init ) {
						window.eoxiaJS[key][slug].init();
					}

				}
			}

			window.eoxiaJS.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.eoxiaJS ) {
			if ( window.eoxiaJS[key].refresh ) {
				window.eoxiaJS[key].refresh();
			}

			for ( slug in window.eoxiaJS[key] ) {

				if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].refresh ) {
					window.eoxiaJS[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.eoxiaJS.init );
}

/**
 * Initialise l'objet "modal" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.modal = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.init = function() {
	window.eoxiaJS.modal.event();
};

/**
 * La méthode contenant tous les événements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.event = function() {
	$( document ).on( 'click', '.modal-close', window.eoxiaJS.modal.closeModal );
	$( document ).on( 'click', '.modal-open', window.eoxiaJS.modal.openModal );
	$( document ).on( 'click', '.modal-refresh', window.eoxiaJS.modal.refreshModal );
};

/**
 * Open Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.openModal = function ( event ) {
	let idSelected = $(this).attr('value');
	if (document.URL.match(/#/)) {
		var urlWithoutTag = document.URL.split(/#/)[0]
	} else {
		var urlWithoutTag = document.URL
	}
	history.pushState({ path:  document.URL}, '', urlWithoutTag);

	// Open modal media gallery.
	 if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
		$('#media_gallery').attr('value', idSelected);
		$('#media_gallery').find('.type-from').attr('value', $(this).find('.type-from').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
	}

	// Open modal patch note.
	if ($(this).hasClass('show-patchnote')) {
		$('.fiche .wpeo-modal-patchnote').addClass('modal-active');
	}

	$('.notice').addClass('hidden');
};

/**
 * Close Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.closeModal = function ( event ) {
	$(this).closest('.modal-active').removeClass('modal-active')
	$('.clicked-photo').attr('style', '');
	$('.clicked-photo').removeClass('clicked-photo');
	$('.notice').addClass('hidden');
};

/**
 * Refresh Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.refreshModal = function ( event ) {
	window.location.reload();
};

/**
 * Initialise l'objet "mediaGallery" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 8.2.0
 */
window.eoxiaJS.mediaGallery = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.init = function() {
	window.eoxiaJS.mediaGallery.event();
};

/**
 * La méthode contenant tous les événements pour le mediaGallery.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.event = function() {
	// Photos
	$( document ).on( 'click', '.clickable-photo', window.eoxiaJS.mediaGallery.selectPhoto );
	$( document ).on( 'click', '.save-photo', window.eoxiaJS.mediaGallery.savePhoto );
	$( document ).on( 'change', '.flat.minwidth400.maxwidth200onsmartphone', window.eoxiaJS.mediaGallery.sendPhoto );
	$( document ).on( 'click', '.clicked-photo-preview', window.eoxiaJS.mediaGallery.previewPhoto );
	$( document ).on( 'input', '.form-element #search_in_gallery', window.eoxiaJS.mediaGallery.handleSearch );
	$( document ).on( 'click', '.media-gallery-unlink', window.eoxiaJS.mediaGallery.unlinkFile );
	$( document ).on( 'click', '.media-gallery-favorite', window.eoxiaJS.mediaGallery.addToFavorite );
	$( document ).on( 'submit', '#fast-upload-photo-ok', window.eoxiaJS.mediaGallery.fastUpload );
}

/**
 * Select photo.
 *
 * @since   8.2.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.selectPhoto = function( event ) {
	let photoID = $(this).attr('value');
	let parent = $(this).closest('.modal-content')

	if ($(this).hasClass('clicked-photo')) {
		$(this).attr('style', 'none !important')
		$(this).removeClass('clicked-photo')

		if ($('.clicked-photo').length === 0) {
			$(this).closest('.modal-container').find('.save-photo').addClass('button-disable');
		}

	} else {
		parent.closest('.modal-container').find('.save-photo').removeClass('button-disable');

		parent.find('.clickable-photo'+photoID).attr('style', 'border: 5px solid #0d8aff !important');
		parent.find('.clickable-photo'+photoID).addClass('clicked-photo');
	}
};

/**
 * Action save photo to an object.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.savePhoto = function( event, photo, typeFrom, id) {
	let parent = $('#media_gallery')
	let mediaGalleryModal = $(this).closest('.modal-container')

	let filesLinked = ''
	if (photo) {
		photo = photo[0].name
		filesLinked = photo
	} else {
		filesLinked = mediaGalleryModal.find('.clicked-photo')
	}

	let rowId = 0
	if (id > 0) {
		rowId = id
	}  else {
		rowId = parent.attr('value')
	}

	let linkedMedias = $('.table-id-'+rowId+' .linked-medias')

	let type = ''
	if (typeFrom) {
		type = typeFrom
	} else {
		type = $(this).find('.type-from').val()
	}

	let filenames = ''
	if (photo) {
		filenames = photo
		if (document.URL.match(/control_card/)) {
			window.eoxiaJS.loader.display(linkedMedias);
		}
	} else {
		if (filesLinked.length > 0) {
			filesLinked.each(function(  ) {
				filenames += $( this ).find('.filename').val() + 'vVv'
			});
		}
		window.eoxiaJS.loader.display($(this));
	}

	let token = $('.fiche').find('input[name="token"]').val();

	let favorite = filenames
	if (favorite.match('vVv')) {
		favorite = favorite.split('vVv')[0]
		favorite = favorite.replace(/\ /, '')
	}

	let url = document.URL + '&'
	let separator = '&'
	if (url.match(/action=/)) {
		url = document.URL.split(/\?/)[0]
		separator = '?'
	}

	$.ajax({
		url: url + separator + "action=addFiles&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filenames: filenames,
			questionId: rowId,
			type: type
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			parent.removeClass('modal-active')
			if (document.URL.match(/control_card/)) {
				linkedMedias.html($(resp).find('.table-id-'+rowId+' .linked-medias'))
				window.eoxiaJS.control.updateButtonsStatus()

			} else if (document.URL.match(/question_card/)) {
				favorite = favorite.replace(/\ /g, '%20')
				favorite = favorite.replace(/\(/g, '%28')
				favorite = favorite.replace(/\)/g, '%29')
				favorite = favorite.replace(/\+/g, '%2B')

				$('.tabBar .linked-medias.'+type+' .linked-medias-list').load(document.URL + '&favorite_' + type + '=' + favorite + ' .tabBar .linked-medias.'+type+' .linked-medias-list', () => {
					$('.linked-medias.'+type).find('.media-container').find('.media-gallery-favorite .fa-star').first().removeClass('far').addClass('fas')
					let favoriteMedia = $('.linked-medias.'+type).find('.media-container').find('.media-gallery-favorite .filename').attr('value')
					$('#'+type).val(favoriteMedia)
				})
			}

			$('.wpeo-modal.modal-photo').html($(resp).find('.wpeo-modal.modal-photo .modal-container'))
		},
		error: function ( ) {
			modalFrom.find('.messageErrorSavePhoto').removeClass('hidden')
		}
	});
};

/**
 * Action handle search in medias
 *
 * @since   8.2.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.handleSearch = function( event ) {
	let searchQuery = $('#search_in_gallery').val()
	let photos = $('.center.clickable-photo')

	photos.each(function(  ) {
		$( this ).text().trim().match(searchQuery) ? $(this).show() : $(this).hide()
	});
};

/**
 * Action send photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.sendPhoto = function( event, file, typeFrom, id ) {
	if (event) {
		event.preventDefault()
	}

	let files    = '';
	if (file) {
		files = file;
	} else {
		files = $(this).prop("files");
	}

	let formdata = new FormData();
	let elementParent = $('.modal-container').find('.ecm-photo-list-content');
	let actionContainerSuccess = $('.messageSuccessSendPhoto');
	let actionContainerError = $('.messageErrorSendPhoto');

	window.eoxiaJS.loader.display($('#media_gallery').find('.modal-content'));
	$.each(files, function(index, file) {
		formdata.append("userfile[]", file);
	})
	let url = document.URL + '&'
	let separator = '&'
	if (url.match(/action=/)) {
		url = document.URL.split(/\?/)[0]
		separator = '?'
	}

	let token = $('.fiche').find('input[name="token"]').val();

	$.ajax({
		url:  url + separator + "action=uploadPhoto&token=" + token,
		type: "POST",
		data: formdata,
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			window.eoxiaJS.loader.display(elementParent);
			elementParent.load( document.URL + ' .ecm-photo-list');
			elementParent.removeClass('wpeo-loader');
			actionContainerSuccess.removeClass('hidden');
			if (file) {
				window.eoxiaJS.mediaGallery.savePhoto('', files, typeFrom, id)
			}

		},
		error: function ( ) {
			actionContainerError.removeClass('hidden');
		}
	})
};

/**
 * Action preview photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.previewPhoto = function( event ) {
	var checkExist = setInterval(function() {
		if ($('.ui-dialog').length) {
			clearInterval(checkExist);
			$( document ).find('.ui-dialog').addClass('preview-photo');
		}
	}, 100);
};

/**
 * Action fast upload.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.fastUpload = function( typeFrom ) {
	let id = 0

	if (typeFrom == 'photo_ok') {
		var files = $('#fast-upload-photo-ok').prop('files');
	} else if (typeFrom == 'photo_ko') {
		var files = $('#fast-upload-photo-ko').prop('files');
	} else if (typeFrom.match(/answer_photo/)) {
		id = typeFrom.split(/_photo/)[1]
		typeFrom = 'answer_photo'
		var files = $('#fast-upload-answer-photo'+id).prop('files');
	}
	window.eoxiaJS.mediaGallery.sendPhoto('', files, typeFrom, id)
};

/**
 * Action unlink photo.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.unlinkFile = function( event ) {

	event.preventDefault()
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let type = $(this).closest('tr').find('.type-from').val()
	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let mediaContainer = $(this).closest('.media-container')
	let previousPhoto = null
	let previousName = ''
	let newPhoto = ''

	let token = $('.fiche').find('input[name="token"]').val();

	//window.eoxiaJS.loader.display($(this).closest('.media-container'));

	document.URL.match('/?/') ? querySeparator = '&' : 1

	//let riskAssessmentPhoto = $('.risk-evaluation-photo-'+element_linked_id)
	//previousPhoto = $(this).closest('.tabBar').find('.clicked-photo-preview')
	//previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]
	//
	//if (previousName == filename.replace(/\./, '_small.')) {
	//	newPhoto = previousPhoto[0].src.replace(previousName, '')
	//} else {
	//	newPhoto = previousPhoto[0].src
	//}
	let url = document.URL + '&'
	let separator = '&'
	if (url.match(/action=/)) {
		url = document.URL.split(/\?/)[0]
		separator = '?'
	}
	$.ajax({
		url: url + separator + "action=unlinkFile&token=" + token,
		type: "POST",
		data: JSON.stringify({
			filename: filename,
			type: type,
			id: currentElementID
		}),
		processData: false,
		success: function ( ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			//riskAssessmentPhoto.each( function() {
			//	$(this).find('.clicked-photo-preview').attr('src',newPhoto )
			//});
			mediaContainer.hide()
		}
	});

};

/**
 * Action add photo to favorite.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.addToFavorite = function( event ) {
	event.preventDefault()
	let filename = $(this).closest('.media-gallery-favorite').find('.filename').attr('value')

	//change star button style
	let previousFavorite = $(this).closest('.linked-medias').find('.fas.fa-star')
	let newFavorite = $(this).find('.far.fa-star')

	previousFavorite.removeClass('fas')
	previousFavorite.addClass('far')
	newFavorite.addClass('fas')
	newFavorite.removeClass('far')

	if (filename.length > 0) {
		$(this).closest('.linked-medias').find('.favorite-photo').val(filename)
	}

};


/**
 * @namespace EO_Framework_Loader
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

/*
 * Gestion du loader.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! window.eoxiaJS.loader ) {

	/**
	 * [loader description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @type {Object}
	 */
	window.eoxiaJS.loader = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.loader.init = function() {
		window.eoxiaJS.loader.event();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.loader.event = function() {
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {void} element [description]
	 * @returns {void}         [description]
	 */
	window.eoxiaJS.loader.display = function( element ) {
		// Loader spécial pour les "button-progress".
		if ( element.hasClass( 'button-progress' ) ) {
			element.addClass( 'button-load' )
		} else {
			element.addClass( 'wpeo-loader' );
			var el = $( '<span class="loader-spin"></span>' );
			element[0].loaderElement = el;
			element.append( element[0].loaderElement );
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {jQuery} element [description]
	 * @returns {void}         [description]
	 */
	window.eoxiaJS.loader.remove = function( element ) {
		if ( 0 < element.length && ! element.hasClass( 'button-progress' ) ) {
			element.removeClass( 'wpeo-loader' );

			$( element[0].loaderElement ).remove();
		}
	};
}

/**
 * @namespace EO_Framework_Tooltip
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

if ( ! window.eoxiaJS.tooltip ) {

	/**
	 * [tooltip description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @type {Object}
	 */
	window.eoxiaJS.tooltip = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.tooltip.init = function() {
		window.eoxiaJS.tooltip.event();
	};

	window.eoxiaJS.tooltip.tabChanged = function() {
		$( '.wpeo-tooltip' ).remove();
	}

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.tooltip.event = function() {
		$( document ).on( 'mouseenter touchstart', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onEnter );
		$( document ).on( 'mouseleave touchend', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onOut );
	};

	window.eoxiaJS.tooltip.onEnter = function( event ) {
		window.eoxiaJS.tooltip.display( $( this ) );
	};

	window.eoxiaJS.tooltip.onOut = function( event ) {
		window.eoxiaJS.tooltip.remove( $( this ) );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.eoxiaJS.tooltip.display = function( element ) {
		var direction = ( $( element ).data( 'direction' ) ) ? $( element ).data( 'direction' ) : 'top';
		var el = $( '<span class="wpeo-tooltip tooltip-' + direction + '">' + $( element ).attr( 'aria-label' ) + '</span>' );
		var pos = $( element ).position();
		var offset = $( element ).offset();
		$( element )[0].tooltipElement = el;
		$( 'body' ).append( $( element )[0].tooltipElement );

		if ( $( element ).data( 'color' ) ) {
			el.addClass( 'tooltip-' + $( element ).data( 'color' ) );
		}

		var top = 0;
		var left = 0;

		switch( $( element ).data( 'direction' ) ) {
			case 'left':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = ( offset.left - el.outerWidth() - 10 ) + 3 + 'px';
				break;
			case 'right':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = offset.left + $( element ).outerWidth() + 8 + 'px';
				break;
			case 'bottom':
				top = ( offset.top + $( element ).height() + 10 ) + 10 + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			case 'top':
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			default:
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
		}

		el.css( {
			'top': top,
			'left': left,
			'opacity': 1
		} );

		$( element ).on("remove", function() {
			$( $( element )[0].tooltipElement ).remove();

		} );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.eoxiaJS.tooltip.remove = function( element ) {
		if ( $( element )[0] && $( element )[0].tooltipElement ) {
			$( $( element )[0].tooltipElement ).remove();
		}
	};
}

/**
 * Initialise l'objet "notice" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 */
window.eoxiaJS.notice = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.init = function() {
	window.eoxiaJS.notice.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.event = function() {
	$(document).on('click', '.notice-close', window.eoxiaJS.notice.closeNotice);
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.closeNotice = function() {
	$(this).closest('.notice').fadeOut(function () {
		$(this).closest('.notice').addClass("hidden");
	});

	if ($(this).hasClass('notice-close-forever')) {
		let token = $(this).closest('.notice').find('input[name="token"]').val();
		let querySeparator = '?';

		document.URL.match(/\?/) ? querySeparator = '&' : 1;

		$.ajax({
			url: document.URL + querySeparator + 'action=closenotice&token='+token,
			type: "POST",
		});
	}
};

/**
 * Initialise l'objet "question" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.question = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.question.init = function() {
	window.eoxiaJS.question.event();
};

/**
 * La méthode contenant tous les événements pour le question.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.question.event = function() {
	$( document ).on( 'click', '.clicked-photo-preview', window.eoxiaJS.question.previewPhoto );
	$( document ).on( 'click', '.ui-dialog-titlebar-close', window.eoxiaJS.question.closePreviewPhoto );
	$( document ).on( 'click', '#show_photo', window.eoxiaJS.question.showPhoto );
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
window.eoxiaJS.question.previewPhoto = function ( event ) {
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
window.eoxiaJS.question.closePreviewPhoto = function ( event ) {
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
window.eoxiaJS.question.showPhoto = function() {
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
window.eoxiaJS.sheet = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.eoxiaJS.sheet.init = function() {
	window.eoxiaJS.sheet.event();
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.eoxiaJS.sheet.event = function() {
};

/**
 * Initialise l'objet "control" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.control = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.control.init = function() {
	window.eoxiaJS.control.event();
};

/**
 * La méthode contenant tous les événements pour le control.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.control.event = function() {
	$( document ).on( 'click', '.answer:not(.disable)', window.eoxiaJS.control.selectAnswer );
	$( document ).on( 'keyup', '.question-comment', window.eoxiaJS.control.writeComment );
	$( document ).on( 'keyup', '.question-comment', window.eoxiaJS.control.showCommentUnsaved );
	$( document ).on( 'change', '#fk_product', window.eoxiaJS.control.reloadProductLot );
	$( document ).on( 'change', '#fk_project', window.eoxiaJS.control.reloadTask );
	$( document ).on( 'click', '.validateButton', window.eoxiaJS.control.getAnswerCounter);
	$( document ).on( 'change', '#fk_sheet', window.eoxiaJS.control.showSelectObjectLinked);
	//$( document ).on( 'click', '#select_all_answer', window.eoxiaJS.control.selectAllAnswer);
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
window.eoxiaJS.control.selectAnswer = function ( event ) {
	$(this).closest('.table-cell').find('span').removeClass( 'active' );
	$(this).closest('span').addClass( 'active' );
	$(this).closest('.table-cell').find('.question-answer').val($(this).attr('value'))

	let postName = $(this).closest('.table-cell').find('.question-answer').attr('name')
	let postValue = $(this).closest('.table-cell').find('.question-answer').val()
	//let actualSavePost = $(this).closest('.tabBar').find('.saveButton').attr('href')
	let actualValidatePost = $(this).closest('.tabBar').find('.validateButton').attr('href')
	//$(this).closest('.tabBar').find('.saveButton').attr('href', actualSavePost + '&' + postName + '=' + postValue)
	$(this).closest('.tabBar').find('.validateButton').attr('href', actualValidatePost + '&' + postName + '=' + postValue)
	window.eoxiaJS.control.updateButtonsStatus()
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
window.eoxiaJS.control.writeComment = function ( event ) {

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
window.eoxiaJS.control.showCommentUnsaved = function ( event ) {
	if (!$(this).hasClass('show-comment-unsaved-message')) {
		$(this).after('<p style="color:red">Commentaire non enregistré</p>');
		$(this).addClass('show-comment-unsaved-message');
	}
	window.eoxiaJS.control.updateButtonsStatus()
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
window.eoxiaJS.control.updateButtonsStatus = function (  ) {
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
window.eoxiaJS.control.reloadProductLot = function ( event ) {
	let selectTitle = $(this).closest('td').find('#select2-fk_product-container').attr('title')
	let productRef = selectTitle.split(/ /)[0]
	let token = $('.id-container').find('input[name="token"]').val();
	let sheetID = $('#sheetID').val();
	let action = '?action=create'
	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&token=' + token + '&fk_sheet=' + sheetID

	$.ajax({
		url: urlToGo,
		type: "POST",
		data: JSON.stringify({
			productRef: productRef,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
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
window.eoxiaJS.control.reloadTask = function ( event ) {
	let selectTitle = $(this).closest('td').find('#select2-fk_project-container').attr('title')
	let projectRef = selectTitle.split(/ /)[0]
	let projectRef2 = projectRef.slice(0, -1)
	let token = $('.id-container').find('input[name="token"]').val();
	let sheetID = $('#sheetID').val();
	let action = '?action=create'
	let urlToGo = document.URL + (document.URL.match(/\?action=create/) ? '' : action) + '&token=' + token + '&fk_sheet=' + sheetID

	$.ajax({
		url: urlToGo,
		type: "POST",
		data: JSON.stringify({
			projectRef: projectRef2,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.task-container').html($(resp).find('.task-content'))
		},
		error: function ( ) {
		}
	});
	//$(this).closest('.control-table').find('.lot-container').load(document.URL+'&productRef='+productRef + ' .lot-content')
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
window.eoxiaJS.control.getAnswerCounter = function ( event ) {
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
window.eoxiaJS.control.showSelectObjectLinked = function ( event ) {
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

	window.eoxiaJS.loader.display($('.tabBar.tabBarWithBottom tbody'))
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
 * Initialise l'objet "menu" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.menu = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.init = function() {
	window.eoxiaJS.menu.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.event = function() {
	$(document).on( 'click', ' .blockvmenu', window.eoxiaJS.menu.toggleMenu);
	$(document).ready(function() { window.eoxiaJS.menu.setMenu()});
}

/**
 * Action Toggle main menu.
 *
 * @since   8.5.0
 * @version 9.0.1
 *
 * @return {void}
 */
window.eoxiaJS.menu.toggleMenu = function() {

	var menu = $(this).closest('#id-left').find('a.vmenu, font.vmenudisabled, span.vmenu, a.vsmenu');
	var elementParent = $(this).closest('#id-left').find('div.vmenu')
	var text = '';

	if ($(this).find('.minimizeMenu').length > 0) {

		menu.each(function () {
			text = $(this).html().split('</i>');
			if (text[1].match(/&gt;/)) {
				text[1] = text[1].replace(/&gt;/, '')
			}
			$(this).attr('title', text[1])
			$(this).html(text[0]);
		});

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();

		$('.minimizeMenu').html($('.minimizeMenu').html() + ' >')

		$(this).find('.minimizeMenu').removeClass('minimizeMenu').addClass('maximizeMenu');
		localStorage.setItem('maximized', 'false')

	} else if ($(this).find('.maximizeMenu').length > 0) {

		menu.each(function () {
			$(this).html($(this).html().replace('&gt;','') + ' ' + $(this).attr('title'));
		});

		elementParent.css('width', '188px');
		elementParent.find('.blockvmenusearch').show();
		$('div.menu_titre').attr('style', 'width: 188px !important')
		$('div.menu_contenu').attr('style', 'width: 188px !important')

		localStorage.setItem('maximized', 'true')

		$(this).find('.maximizeMenu').removeClass('maximizeMenu').addClass('minimizeMenu');
	}
};

/**
 * Action set  menu.
 *
 * @since   8.5.0
 * @version 9.0.1
 *
 * @return {void}
 */
window.eoxiaJS.menu.setMenu = function() {
	$('.minimizeMenu').parent().parent().parent().attr('style', 'cursor:pointer ! important')

	if (localStorage.maximized == 'false') {
		$('#id-left').attr('style', 'display:none !important')
	}

	if (localStorage.maximized == 'false') {
		var text = '';
		var menu = $('#id-left').find('a.vmenu, font.vmenudisabled, span.vmenu, a.vsmenu');
		var elementParent = $(document).find('div.vmenu')

		menu.each(function () {
			text = $(this).html().split('</i>');
			$(this).attr('title', text[1])
			$(this).html(text[0]);
		});

		$('#id-left').attr('style', 'display:block !important')
		$('div.menu_titre').attr('style', 'width: 50px !important')
		$('div.menu_contenu').attr('style', 'width: 50px !important')

		$('.minimizeMenu').html($('.minimizeMenu').html() + ' >')
		$('.minimizeMenu').removeClass('minimizeMenu').addClass('maximizeMenu');

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();
	}
};

/**
 * Initialise l'objet "keyEvent" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.keyEvent = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.init = function() {
	window.eoxiaJS.keyEvent.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.event = function() {
	$( document ).on( 'keydown', window.eoxiaJS.keyEvent.keyup );
}

/**
 * Action modal close & validation with key events
 *
 * @since   1.0.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.keyup = function( event ) {
	if ( 'Escape' === event.key  ) {
		$(this).find('.modal-active .modal-close .fas.fa-times').first().click();
	}

	if ( 'Enter' === event.key )  {
		event.preventDefault()
		if (!$('input, textarea').is(':focus')) {
			$(this).find('.modal-active .modal-footer .wpeo-button').not('.button-disable').first().click();
		} else {
			$('textarea:focus').val($('textarea:focus').val() + '\n')
		}
	}
};

