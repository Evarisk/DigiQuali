/* Javascript library of module Saturne */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
 * @copyright 2015-2023 Evarisk
 */

if ( ! window.digiquali ) {
	/**
	 * [digiquali description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Object}
	 */
	window.digiquali = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.digiquali.scriptsLoaded = false;
}

if ( ! window.digiquali.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiquali.init = function() {
		window.digiquali.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiquali.load_list_script = function() {
		if ( ! window.digiquali.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.digiquali ) {

				if ( window.digiquali[key].init ) {
					window.digiquali[key].init();
				}

				for ( slug in window.digiquali[key] ) {

					if ( window.digiquali[key] && window.digiquali[key][slug] && window.digiquali[key][slug].init ) {
						window.digiquali[key][slug].init();
					}

				}
			}

			window.digiquali.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiquali.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.digiquali ) {
			if ( window.digiquali[key].refresh ) {
				window.digiquali[key].refresh();
			}

			for ( slug in window.digiquali[key] ) {

				if ( window.digiquali[key] && window.digiquali[key][slug] && window.digiquali[key][slug].refresh ) {
					window.digiquali[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.digiquali.init );
}

