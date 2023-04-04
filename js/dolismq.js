/* Javascript library of module Saturne */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
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

