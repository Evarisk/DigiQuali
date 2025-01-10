<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/medias/medias_answer_modal.tpl.php
 * \ingroup saturne
 * \brief   Template page for medias answer modal
 */

/**
 * The following vars must be defined :
 * Global : $langs
 */

?>

<!-- Modal medias answer-->
<div class="wpeo-modal modal-medias-answer" id="modal-medias-answer-<?php echo $question->id; ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header-->
        <div class="modal-header">
            <div class="modal-header-content">
                <h2 class="modal-title"><?php echo $langs->transnoentities('LinkedMedia'); ?></h2>
                <div class="modal-description"><?php print $question->label; ?></div>
            </div>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content-->
        <div class="modal-content" id="#modalContent">
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
        <!-- Modal-Footer-->
        <div class="modal-footer"></div>
    </div>
</div>
