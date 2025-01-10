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
 * \file    lib/digiquali_answer.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Control
 */


/**
 * Create pictos dropdown string
 *
 * @param  CommonObject $object Object
 * @return string               dropdown html output
 * @throws Exception
 */
function answer_pictos_dropdown($selected = ''): string
{
	$pictosArray = get_answer_pictos_array();

	$out = '<div class="wpeo-dropdown dropdown-large dropdown-grid answer-picto padding">';
	$out .= '<input class="input-hidden-picto" type="hidden" name="answerPicto" value="'. (dol_strlen($selected) > 0 ? $selected : '') .'" />
			<div class="dropdown-toggle dropdown-add-button button-picto">';

	if (dol_strlen($selected) > 0) {
		$out .= '<span class="wpeo-button button-square-50 button-grey">';
		$out .= $pictosArray[$selected]['picto_source'];
		$out .= '</span>';
	} else {
		$out .= '<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-arrow-right button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>';
	}
	$out .= '</div>';
	$out .= '<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">';
	if ( ! empty($pictosArray) ) {
		foreach ($pictosArray as $pictoName => $picto) {
			$out .= '<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="" data-id="'. $picto['position'] .'" aria-label="'. $picto['name'] .'" data-label="'. $pictoName.'">';
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

	$pictosArray = [
		'' => [
			'name' => $langs->transnoentities('None'),
			'picto_source' => $langs->transnoentities('None'),
			'position' => 0,
		],
		'check' => [
			'name' => $langs->transnoentities('Ok'),
			'picto_source' => '<i class="fas fa-check"></i>',
			'position' => 1
		],
		'times' => [
			'name' => $langs->transnoentities('Ko'),
			'picto_source' => '<i class="fas fa-times"></i>',
			'position' => 2
		],
		'tools' => [
			'name' => $langs->transnoentities('ToFix'),
			'picto_source' => '<i class="fas fa-tools"></i>',
			'position' => 3
		],
		'N/A' => [
			'name' => $langs->transnoentities('NonApplicable'),
			'picto_source' => 'N/A',
			'position' => 4
		],
	];
	return $pictosArray;
}

/**
 * Return answer pictos array
 *
 * @param  Question     $questionType Question type
 * @return CommonObject $object
 * @throws Exception
 */
function get_answer_tpl(Question $question, CommonObject $object, $questionAnswer, $answerLinked, $answer = null): string
{
    $out = '';

    $disabled = ($object->status > $object::STATUS_DRAFT ? ' disabled' : '');

    switch ($question->type) {
        case 'Text':
            $out .= '<div>';
            //@TODO Translate placeholder
            $out .= '<textarea placeholder="Écrire votre réponse" class="question-textarea question-answer" name="answer' . $question->id . '"' . $disabled . '>' . $questionAnswer . '</textarea>';
            $out .= '</div>';
            break;
        case 'Percentage':
            $out .= '<div class="percentage-cell">';
            $out .= img_picto('', 'fontawesome_fa-frown_fas_#D53C3D_3em', 'class="range-image"');
            $out .= '<input type="range" class="search_component_input range question-answer" name="answer' . $question->id . '" min="0" max="100" value="' . $questionAnswer . '"' . $disabled . '>';
            $out .= img_picto('', 'fontawesome_fa-grin_fas_#57AD39_3em', 'class="range-image"');
            $out .= '</div>';
            break;
        case 'Range':
            $out .= '<div>';
            $out .= '<input type="number" class="question-answer" name="answer' . $question->id . '" value="' . $questionAnswer . '"' . $disabled . '>';
            $out .= '</div>';
            break;
        case 'UniqueChoice':
        case 'OkKo':
        case 'OkKoToFixNonApplicable':
        case 'MultipleChoices':
            $answerList  = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = ' . Answer::STATUS_VALIDATED . ' AND t.fk_question = ' . $question->id]);
            $pictosArray = get_answer_pictos_array();

            if (strpos($questionAnswer, ',') !== false) {
                $questionAnswers = explode(',', $questionAnswer);
            } else {
                $questionAnswers = [$questionAnswer];
            }

            $out .= '<div class="table-cell select-answer answer-cell">';
            $out .= '<input type="hidden" class="question-answer" name="answer' . $question->id . '" value="0">';
            if (is_array($answerList) && !empty($answerList)) {
                foreach($answerList as $answerLinked) {
                    $out .= '<input type="hidden" class="answer-color answer-color-' . $answerLinked->position . '" value="' . $answerLinked->color . '">';
                    $out .= '<span class="answer' . (!empty($answerLinked->pictogram) ? ' answer-icon' : '' ) . ($question->type == 'MultipleChoices' ? ' multiple-answers square' : ' single-answer') . (in_array($answerLinked->position, $questionAnswers) ? ' active' : '') . ($object->status > 0 ? ' disable' : '') . '" style="' . (in_array($answerLinked->position, $questionAnswers) ? 'background:' . $answerLinked->color . '; ' : '') . 'color:' . $answerLinked->color . ';' . 'box-shadow: 0 0 0 3px ' . $answerLinked->color . ';" value="' . $answerLinked->position . '">';
                    if (!empty($answerLinked->pictogram)) {
                        $out .= $pictosArray[$answerLinked->pictogram]['picto_source'];
                    } else {
                        $out .= $answerLinked->value;
                    }
                    $out .= '</span>';
                }
            }
            $out .= '</div>';
            break;
    }

    return $out;
}
