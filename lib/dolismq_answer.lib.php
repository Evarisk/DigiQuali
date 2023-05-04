<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/dolismq_answer.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Control
 */


/**
 * Create pictos dropdown string
 *
 * @param  CommonObject $object Object
 * @return string               dropdown html output
 * @throws Exception
 */
function answer_pictos_dropdown($selected = -1): string
{
	$pictosArray = get_answer_pictos_array();

	$out = '<div class="wpeo-dropdown dropdown-large dropdown-grid answer-picto padding">';
	$out .= '<input class="input-hidden-picto" type="hidden" name="answerPicto" value="'. ($selected >=0 ? $selected : 0) .'" />
			<div class="dropdown-toggle dropdown-add-button button-picto">';
	if ($selected >= 0) {
		$out .= '<span class="wpeo-button button-square-50 button-grey">';
		$out .= $pictosArray[$selected]['picto_source'];
		$out .= '</span>';
	} else {
		$out .= '<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>';
	}
	$out .= '</div>';
	$out .= '<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">';
	if ( ! empty($pictosArray) ) {
		foreach ($pictosArray as $picto) {
			$out .= '<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="" data-id="'. $picto['position'] .'" aria-label="'. $picto['name'].'">';
			$out .= '<div class="wpeo-button button-grey">';
			$out .= $picto['picto_source'];
			$out .= '</div>';
			$out .= '</li>';
		}
	}
	$out .= '</ul>';
	$out .= '</div>';
	return $out;
}

/**
 * Return answer pictos array
 *
 * @param  CommonObject $object Object
 * @return array                Array of pictos
 * @throws Exception
 */
function get_answer_pictos_array(): array
{
	global $langs;

	$fontAwesomePictosList = dol_dir_list(DOL_DOCUMENT_ROOT . '\theme\common\fontawesome-5\svgs\solid');

	$position = 0;
	if (is_array($fontAwesomePictosList) && !empty($fontAwesomePictosList)) {
		foreach($fontAwesomePictosList as $fontAwesomePicto) {
			$pictoInfos = pathinfo($fontAwesomePicto['name']);
			$pictoName  = $pictoInfos['filename'];

			$pictosArray[] = [
				'name' => ucfirst($pictoName),
				'picto_source' => '<i class="fas fa-'. $pictoName .'"></i>',
				'position' => $position,
			];

			$position++;
		}
	}
//
//	$pictosArray = [
//		[
//			'name' => $langs->transnoentities('None'),
//			'picto_source' => $langs->transnoentities('None'),
//			'position' => 0,
//		],
//		[
//			'name' => $langs->transnoentities('Ok'),
//			'picto_source' => '<i class="fas fa-check"></i>',
//			'position' => 1
//		],
//		[
//			'name' => $langs->transnoentities('Ko'),
//			'picto_source' => '<i class="fas fa-times"></i>',
//			'position' => 2
//		],
//		[
//			'name' => $langs->transnoentities('ToFix'),
//			'picto_source' => '<i class="fas fa-tools"></i>',
//			'position' => 3
//		],
//		[
//			'name' => $langs->transnoentities('NonApplicable'),
//			'picto_source' => 'N/A',
//			'position' => 4
//		],
//	];
	return $pictosArray;
}


