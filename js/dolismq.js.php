/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
<?php
if ( ! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if ( ! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if ( ! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if ( ! defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if ( ! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if ( ! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if ( ! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res    = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>
/**
 * \file    digiriskdolibarr/js/digiriskdolibarr.js.php
 * \ingroup digiriskdolibarr
 * \brief   JavaScript file for module DigiriskDolibarr.
 */

/* Javascript library of module DigiriskDolibarr */

'use strict';
/**
 * @namespace EO_Framework_Init
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2021 Eoxia
 */

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

	// Open modal evaluation.
	if ($(this).hasClass('risk-evaluation-add')) {
		$('#risk_evaluation_add'+idSelected).addClass('modal-active');
		$('.risk-evaluation-create'+idSelected).attr('value', idSelected);
	} else if ($(this).hasClass('risk-evaluation-list')) {
		$('#risk_evaluation_list' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
		$('#media_gallery').attr('value', idSelected);
		$('#media_gallery').find('.type-from').attr('value', $(this).find('.type-from').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
	} else if ($(this).hasClass('risk-evaluation-edit')) {
		$('#risk_evaluation_edit' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('evaluator-add')) {
		$('#evaluator_add' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-medias-linked') && $(this).hasClass('digirisk-element')) {
		$('#digirisk_element_medias_modal_' + idSelected).addClass('modal-active');
	}

	// Open modal risk.
	if ($(this).hasClass('risk-add')) {
		$('#risk_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risk-edit')) {
		$('#risk_edit' + idSelected).addClass('modal-active');
	}

	// Open modal riskassessment task.
	if ($(this).hasClass('riskassessment-task-add')) {
		$('#risk_assessment_task_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-edit')) {
		$('#risk_assessment_task_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-list')) {
		$('#risk_assessment_task_list' + idSelected).addClass('modal-active');
	}

	// Open modal risksign.
	if ($(this).hasClass('risksign-add')) {
		$('#risksign_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-edit')) {
		$('#risksign_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-photo')) {
		$(this).closest('.risksign-photo-container').find('#risksign_photo' + idSelected).addClass('modal-active');
	}

	// Open modal signature.
	if ($(this).hasClass('modal-signature-open')) {
		$('#modal-signature' + idSelected).addClass('modal-active');
		window.eoxiaJS.signature.modalSignatureOpened( $(this) );
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
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature = {};

/**
 * Initialise le canvas signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.canvas;

/**
 * Initialise le boutton signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.init = function() {
	window.eoxiaJS.signature.event();
};

/**
 * La méthode contenant tous les événements pour la signature.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.event = function() {
	$( document ).on( 'click', '.signature-erase', window.eoxiaJS.signature.clearCanvas );
	$( document ).on( 'click', '.signature-validate', window.eoxiaJS.signature.createSignature );
	$( document ).on( 'click', '.auto-download', window.eoxiaJS.signature.autoDownloadSpecimen );
};

/**
 * Open modal signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.modalSignatureOpened = function( triggeredElement ) {
	window.eoxiaJS.signature.buttonSignature = triggeredElement;

	var ratio =  Math.max( window.devicePixelRatio || 1, 1 );

	window.eoxiaJS.signature.canvas = document.querySelector('#modal-signature' + triggeredElement.attr('value') + ' canvas' );

	window.eoxiaJS.signature.canvas.signaturePad = new SignaturePad( window.eoxiaJS.signature.canvas, {
		penColor: "rgb(0, 0, 0)"
	} );

	window.eoxiaJS.signature.canvas.width = window.eoxiaJS.signature.canvas.offsetWidth * ratio;
	window.eoxiaJS.signature.canvas.height = window.eoxiaJS.signature.canvas.offsetHeight * ratio;
	window.eoxiaJS.signature.canvas.getContext( "2d" ).scale( ratio, ratio );
	window.eoxiaJS.signature.canvas.signaturePad.clear();

	var signature_data = $( '#signature_data' + triggeredElement.attr('value') ).val();
	window.eoxiaJS.signature.canvas.signaturePad.fromDataURL(signature_data);
};

/**
 * Action Clear sign
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.clearCanvas = function( event ) {
	var canvas = $( this ).closest( '.modal-signature' ).find( 'canvas' );
	canvas[0].signaturePad.clear();
};

/**
 * Action create signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.createSignature = function() {
	let elementSignatory = $(this).attr('value');
	let elementRedirect  = $(this).find('#redirect' + elementSignatory).attr('value');
	let elementZone  = $(this).find('#zone' + elementSignatory).attr('value');
	let actionContainerSuccess = $('.noticeSignatureSuccess');
	var signatoryIDPost = '';
	if (elementSignatory !== 0) {
		signatoryIDPost = '&signatoryID=' + elementSignatory;
	}

	if ( ! $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].signaturePad.isEmpty() ) {
		var signature = $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].toDataURL();
	}

	var url = '';
	var type = '';
	if (elementZone == "private") {
		url = document.URL + '&action=addSignature' + signatoryIDPost;
		type = "POST"
	} else {
		url = document.URL + '&action=addSignature' + signatoryIDPost;
		type = "POST";
	}
	$.ajax({
		url: url,
		type: type,
		processData: false,
		contentType: 'application/octet-stream',
		data: signature,
		success: function( resp ) {
			if (elementZone == "private") {
				actionContainerSuccess.html($(resp).find('.noticeSignatureSuccess .all-notice-content'));
				actionContainerSuccess.removeClass('hidden');
				$('.signatures-container').html($(resp).find('.signatures-container'));
			} else {
				window.location.replace(elementRedirect);
			}
		},
		error: function ( ) {
			alert('Error')
		}
	});
};

/**
 * Download signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.download = function(fileUrl, filename) {
	var a = document.createElement("a");
	a.href = fileUrl;
	a.setAttribute("download", filename);
	a.click();
}

/**
 * Auto Download signature specimen
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.autoDownloadSpecimen = function( event ) {
	let element = $(this).closest('.file-generation')
	let url = document.URL + '&action=builddoc'
	$.ajax({
		url: url,
		type: "POST",
		success: function ( ) {
			let filename = element.find('.specimen-name').attr('value')
			let path = element.find('.specimen-path').attr('value')

			window.eoxiaJS.signature.download(path + filename, filename);
			$.ajax({
				url: document.URL + '&action=remove_file',
				type: "POST",
				success: function ( ) {
				},
				error: function ( ) {
				}
			});
		},
		error: function ( ) {
		}
	});
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
window.eoxiaJS.mediaGallery.savePhoto = function( event ) {
	let parent = $('#media_gallery')
	let idToSave = $(this).attr('value')
	let mediaGalleryModal = $(this).closest('.modal-container')
	let filesLinked = mediaGalleryModal.find('.clicked-photo')
	//let modalFrom = $('.modal-active:not(.modal-photo)')
	//
	//let riskId = modalFrom.attr('value')
	let mediaLinked = ''
	let type = $(this).find('.type-from').val()

	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let filenames = ''
	if (filesLinked.length > 0) {
		filesLinked.each(function(  ) {
			filenames += $( this ).find('.filename').val() + 'vVv'
		});
	}

	let favorite = filenames
	favorite = favorite.split('vVv')[0]
	favorite = favorite.replace(/\ /, '')
	window.eoxiaJS.loader.display($(this));
	//mediaLinked = modalFrom.find('.element-linked-medias')
	//window.eoxiaJS.loader.display(mediaLinked);
	let url = document.URL + '&'
	let separator = '&'
	if (url.match(/action=create/)) {
		url = document.URL.split(/\?/)[0]
		separator = '?'
	}
	$.ajax({
		url: url + separator + "action=addFiles",
		type: "POST",
		data: JSON.stringify({
			filenames: filenames,
			questionId: currentElementID,
			type: type
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			parent.removeClass('modal-active')
			console.log(resp)
			$('.tabBar').load(document.URL + ' .tabBar')
			//riskAssessmentPhoto.each( function() {
			//	$(this).find('.clicked-photo-preview').attr('src',newPhoto )
			//	$(this).find('.filename').attr('value', favorite.match(/_small/) ? favorite.replace(/\./, '_small.') : favorite)
			//});
			//mediaLinked.load(document.URL+'&favorite='+favorite + ' .element-linked-medias-'+idToSave+'.risk-'+riskId)
			//modalFrom.find('.messageSuccessSavePhoto').removeClass('hidden')
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
window.eoxiaJS.mediaGallery.sendPhoto = function( event ) {

	event.preventDefault()
	let files    = $(this).prop("files");
	let formdata = new FormData();
	let elementParent = $(this).closest('.modal-container').find('.ecm-photo-list-content');
	let actionContainerSuccess = $('.messageSuccessSendPhoto');
	let actionContainerError = $('.messageErrorSendPhoto');
	window.eoxiaJS.loader.display($('#media_gallery').find('.modal-content'));
	$.each(files, function(index, file) {
		console.log(file)
		formdata.append("userfile[]", file);
		console.log(formdata)

	})
	let url = document.URL + '&'
	let separator = '&'
	if (url.match(/action=create/)) {
		url = document.URL.split(/\?/)[0]
		separator = '?'
	}

	$.ajax({
		url:  url + separator + "action=uploadPhoto",
		type: "POST",
		data: formdata,
		processData: false,
		contentType: false,
		success: function ( resp ) {
			console.log(document.URL)

			$('.wpeo-loader').removeClass('wpeo-loader')
			window.eoxiaJS.loader.display(elementParent);
			elementParent.load( document.URL + ' .ecm-photo-list');
			elementParent.removeClass('wpeo-loader');
			actionContainerSuccess.removeClass('hidden');
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
	setTimeout(function(){
		$( document ).find('.ui-dialog').addClass('preview-photo');
	}, 200);
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
	let riskId = $(this).closest('.modal-risk').attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let mediaContainer = $(this).closest('.media-container')
	let previousPhoto = null
	let previousName = ''
	let newPhoto = ''


	window.eoxiaJS.loader.display($(this).closest('.media-container'));

	document.URL.match('/?/') ? querySeparator = '&' : 1

	if (type === 'riskassessment') {
		let riskAssessmentPhoto = $('.risk-evaluation-photo-'+element_linked_id)
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		if (previousName == filename.replace(/\./, '_small.')) {
			newPhoto = previousPhoto[0].src.replace(previousName, '')
		} else {
			newPhoto = previousPhoto[0].src
		}

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkFile",
			type: "POST",
			data: JSON.stringify({
				risk_id: riskId,
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				riskAssessmentPhoto.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
				});
				mediaContainer.hide()
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')
		previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		if (previousName == filename.replace(/\./, '_small.')) {
			newPhoto = previousPhoto[0].src.replace(previousName, '')
		} else {
			newPhoto = previousPhoto[0].src
		}

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkDigiriskElementFile",
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				previousPhoto.attr('src',newPhoto)
				mediaContainer.hide()
				if (element_linked_id === currentElementID) {
					let digiriskBanner = $('.arearef.heightref')
					digiriskBanner.find('input[value="'+filename+'"]').siblings('').hide()
				}
			}
		});
	}

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
	var params = new window.URLSearchParams(window.location.search);
	var id = window.location.search.split(/id=/)[1]
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let mediaContainer = $(this).closest('.media-container')
	let modalFrom = $('.modal-risk.modal-active')
	let riskId = modalFrom.attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	let previousPhoto = null
	let elementPhotos = ''

	//change star button style
	let previousFavorite = $(this).closest('.element-linked-medias').find('.fas.fa-star')
	let newFavorite = $(this).find('.far.fa-star')

	previousFavorite.removeClass('fas')
	previousFavorite.addClass('far')
	newFavorite.addClass('fas')
	newFavorite.removeClass('far')

	document.URL.match('/?/') ? querySeparator = '&' : 1

	window.eoxiaJS.loader.display(mediaContainer);

	if (type === 'riskassessment') {
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		elementPhotos = $('.risk-evaluation-photo-'+element_linked_id+'.risk-'+riskId)

		$(this).closest('.modal-content').find('.risk-evaluation-photo-single .filename').attr('value', filename)
		let previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]
		let saveButton = $(this).closest('.modal-container').find('.risk-evaluation-save')
		saveButton.addClass('button-disable')
		$.ajax({
			url: document.URL + querySeparator + "action=addToFavorite",
			data: JSON.stringify({
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			type: "POST",
			processData: false,
			success: function ( ) {
				let newPhoto = ''
				if (previousName.length > 0 ) {
					newPhoto = previousPhoto[0].src.trim().replace(previousName , filename.replace(/\./, '_small.'))
				} else {
					newPhoto = previousPhoto[0].src.trim() + filename.replace(/\./, '_small.')
				}
				elementPhotos.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
				});
				saveButton.removeClass('button-disable')
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')

		let previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		jQuery.ajax({
			url: document.URL + querySeparator + "action=addDigiriskElementPhotoToFavorite",
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( resp ) {
				let newPhoto = ''
				console.log(id)
				console.log(element_linked_id)
				if (id === element_linked_id) {
					console.log($('.arearef.heightref.valignmiddle.centpercent'))
					console.log($(resp).find('.arearef.heightref.valignmiddle.centpercent'))
					console.log(resp)

					//$('.arearef.heightref.valignmiddle.centpercent').html($(resp).find('.arearef.heightref.valignmiddle.centpercent'))
					$('.arearef.heightref.valignmiddle.centpercent').load(' .arearef.heightref.valignmiddle.centpercent')
				}
				if (previousName.length > 0 ) {
					newPhoto = previousPhoto[0].src.trim().replace(previousName , filename.replace(/\./, '_small.'))
				} else {
					newPhoto = previousPhoto[0].src.trim() + filename.replace(/\./, '_small.')
				}
				previousPhoto.attr('src',newPhoto )
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
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

