<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiquali_answers.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers lines
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Parameters :
 * Objects    : $answer, $object, $objectLine, $sheet
 */

if (is_array($sheet->linkedObjects['digiquali_question']) && !empty($sheet->linkedObjects['digiquali_question'])) {
    foreach ($sheet->linkedObjects['digiquali_question'] as $question) {
        $questionAnswer = '';
        $comment        = '';
        $result         = $objectLine->fetchFromParentWithQuestion($object->id, $question->id);
        if (is_array($result) && !empty($result)) {
            $objectLine = array_shift($result);
            $questionAnswer = $objectLine->answer;
            $comment = $objectLine->comment;
        }
        if (!$user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER or empty($questionAnswer)) {
            ?>
            <div class="question table-id-<?php echo $question->id ?> <?php echo ($objectLine->status == Answer::STATUS_VALIDATED && !empty($questionAnswer) ? ' question-complete' : ''); ?>" data-autoSave="<?php echo getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . 'DET_AUTO_SAVE_ACTION'); ?>">
                <div class="question__header">
                    <div class="question__header-content">
                        <div class="question-title"><?php print $question->label; ?></div>
                        <div class="question-description"><?php print $question->description; ?></div>
                    </div>
                    <div class="question__header-medias">
                        <?php if ($question->show_photo > 0 && getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . '_DISPLAY_MEDIAS')) : ?>
                            <?php
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ok', 'small', '', 0, 0, 0, 100, 100, 0, 0, 0, 'question/' . $question->ref . '/photo_ok', $question, 'photo_ok', 0, 0, 0,1, 'photo-ok', 0);
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ko', 'small', '', 0, 0, 0, 100, 100, 0, 0, 0, 'question/' . $question->ref . '/photo_ko', $question, 'photo_ko', 0, 0, 0,1, 'photo-ko', 0);
                            ?>
                        <?php endif; ?>
                    </div>
                    <div class="question__header-answer">
                        <?php print show_answer_from_question($question, $object, $questionAnswer); ?>
                    </div>
                </div>

                <div class="question__footer">
                    <?php if ($question->enter_comment > 0) : ?>
                        <label class="question__footer-comment">
                            <i class="far fa-comment-dots question-comment-icon"></i>
                            <input class="question-textarea question-comment" name="comment<?php echo $question->id; ?>" placeholder="<?php echo $langs->transnoentities('WriteComment'); ?>" value="<?php echo $comment; ?>" <?php echo ($object->status == 2 ? 'disabled' : ''); ?>>
                        </label>
                    <?php endif; ?>
                    <?php if ($question->authorize_answer_photo > 0) : ?>
                        <div class="question__footer-linked-medias">
                            <div class="linked-medias linked-medias-list answer_photo_<?php echo $question->id ?>">
                                <?php if ($object->status == 0) : ?>
                                    <input hidden multiple class="fast-upload<?php echo getDolGlobalInt('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT') ? '-improvement' : ''; ?>" id="fast-upload-answer-photo<?php echo $question->id ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
                                    <input type="hidden" class="question-answer-photo" id="answer_photo_<?php echo $question->id ?>" name="answer_photo_<?php echo $question->id ?>" value=""/>
                                    <input type="hidden" class="fast-upload-options" data-from-subtype="answer_photo_<?php echo $question->id ?>" data-from-subdir="answer_photo/<?php echo $question->ref ?>"/>
                                    <label for="fast-upload-answer-photo<?php echo $question->id ?>">
                                        <div class="wpeo-button button-square-50">
                                            <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                                        </div>
                                    </label>
                                    <div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="<?php echo $question->id ?>">
                                        <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id ?>" data-from-type="<?php echo $object->element ?>" data-from-subtype="answer_photo_<?php echo $question->id ?>" data-from-subdir="answer_photo/<?php echo $question->ref ?>"/>
                                        <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
                                    </div>
                                <?php endif; ?>
                                <?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }
}
