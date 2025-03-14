<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
		'smile' => [
			'name' => $langs->transnoentities('Smile'),
			'picto_source' => '<i class="fas fa-smile"></i>',
			'position' => 5
		],
		'meh' => [
			'name' => $langs->transnoentities('Meh'),
			'picto_source' => '<i class="fas fa-meh"></i>',
			'position' => 6
		],
		'sad' => [
			'name' => $langs->transnoentities('Sad'),
			'picto_source' => '<i class="fas fa-frown"></i>',
			'position' => 7
		]
	];
	return $pictosArray;
}

/**
 * Interpolate between two colors in a gradient
 *
 * @param  array $gradient   Array of colors with their positions
 * @param  float $percentage Percentage to interpolate
 * @return string           Interpolated color in hex format
 */
function interpolateColor(array $gradient, float $percentage): string {
    ksort($gradient);
    $positions = array_keys($gradient);
    
    // Find the positions surrounding the percentage
    $pos1 = 0;
    foreach ($positions as $pos) {
        if ($pos <= $percentage) {
            $pos1 = $pos;
        } else {
            $pos2 = $pos;
            break;
        }
    }
    
    // If exact match or at edge of gradient
    if (!isset($pos2) || $pos1 == $percentage) {
        return $gradient[$pos1];
    }
    
    // Extract RGB components
    list($r1, $g1, $b1) = sscanf($gradient[$pos1], "#%02x%02x%02x");
    list($r2, $g2, $b2) = sscanf($gradient[$pos2], "#%02x%02x%02x");
    
    // Calculate interpolation factor
    $ratio = ($percentage - $pos1) / ($pos2 - $pos1);
    
    // Interpolate RGB values
    $r = round($r1 + ($r2 - $r1) * $ratio);
    $g = round($g1 + ($g2 - $g1) * $ratio);
    $b = round($b1 + ($b2 - $b1) * $ratio);
    
    return sprintf("#%02X%02X%02X", $r, $g, $b);
}

/**
 * Show answer from question object in HTML output format (textarea, range, select, ...)
 *
 * @param  Question     $question       Question object
 * @param  CommonObject $object         Object (Control, Survey, ...)
 * @param  string       $questionAnswer Answer of the question (ControlLine, SurveyLine, ...)
 * @return string       $out            HTML output
 * @throws Exception
 */
function show_answer_from_question(Question $question, CommonObject $object, string $questionAnswer): string
{
    global $db, $langs;

    $answer = new Answer($db);

    $out            = '';
    $disabled       = ($object->status > $object::STATUS_DRAFT ? ' disabled' : '');
    $questionConfig = json_decode($question->json, true)['config'];

    if ($question->type == 'Text') {
        $out .= '<div>';
        $out .= '<textarea class="question-textarea question-answer" name="answer' . $question->id . '" placeholder="' . $langs->transnoentities('WriteAnswer') . '"' . $disabled . '>' . $questionAnswer . '</textarea>';
        $out .= '</div>';
    } else if ($question->type == 'Percentage' && $questionConfig[$question->type]['isCursor']) {
        $step = 100;
        if (!empty($questionConfig[$question->type]['step'])) {
            $step = $questionConfig[$question->type]['step'];
        }

        $out .= '<div class="percentage-cell">';
        $out .= img_picto('', 'fontawesome_fa-frown_fas_#D53C3D_3em', 'class="range-image"');
        $out .= '<input type="range" class="search_component_input range question-answer" name="answer' . $question->id . '" min="0" max="100" step="' . 100/($step - 1) . '" value="' . $questionAnswer . '"' . $disabled . '>';
        $out .= img_picto('', 'fontawesome_fa-grin_fas_#57AD39_3em', 'class="range-image"');
        $out .= '</div>';
    } else if ($question->type == 'Range') {
        $out .= '<div class="question-number">';
        $out .= '<input type="number" class="question-answer" name="answer' . $question->id . '" placeholder="0" value="' . $questionAnswer . '"' . $disabled . '>';
        $out .= '</div>';
    } else if ($question->type == 'UniqueChoice' || 
               $question->type == 'OkKo' || 
               $question->type == 'OkKoToFixNonApplicable' || 
               $question->type == 'MultipleChoices' || 
               ($question->type == 'Percentage' && !$questionConfig[$question->type]['isCursor'])) {
        
        $answers = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = ' . Answer::STATUS_VALIDATED . ' AND t.fk_question = ' . $question->id]);
        $pictos  = get_answer_pictos_array();

        if (strpos($questionAnswer, ',') !== false) {
            $questionAnswers = explode(',', $questionAnswer);
        } else {
            $questionAnswers = [$questionAnswer];
        }

        $out .= '<div class="table-cell select-answer answer-cell">';
        $out .= '<input type="hidden" class="question-answer" name="answer' . $question->id . '" value="0">';
        if (is_array($answers) && !empty($answers)) {
            foreach($answers as $answer) {
                $out .= '<input type="hidden" class="answer-color answer-color-' . $answer->position . '" value="' . $answer->color . '">';
                $out .= '<span class="answer' . (!empty($answer->pictogram) ? ' answer-icon' : '' ) . ($question->type == 'MultipleChoices' ? ' multiple-answers square' : ' single-answer') . (in_array($answer->position, $questionAnswers) ? ' active' : '') . ($object->status > 0 ? ' disable' : '') . '" style="' . (in_array($answer->position, $questionAnswers) ? 'background:' . $answer->color . '; ' : '') . 'color:' . $answer->color . ';' . 'box-shadow: 0 0 0 3px ' . $answer->color . ';" value="' . $answer->position . '">';
                $out .= !empty($answer->pictogram) ? $pictos[$answer->pictogram]['picto_source'] : $answer->value;
                $out .= '</span>';
            }
        }
        $out .= '</div>';
    }

    return $out;
}
