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
 * Parameters : $permissiontoaddtask
 * Objects    : $answer, $object, $objectLine, $sheet, $project
 * Variable   : $publicInterface
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

        $objectLine->fetchObjectLinked($objectLine->id, $objectLine->element);

        if (!$user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER or empty($questionAnswer)) {
            ?>
            <div class="wpeo-table table-flex table-3 table-id-<?php echo $question->id ?>" data-publicInterface="<?php echo $publicInterface; ?>" data-autoSave="<?php echo getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . 'DET_AUTO_SAVE_ACTION'); ?>">
                <div class="table-row">
                    <!-- Contenu et commentaire -->
                    <div class="table-cell table-full">
                        <div class="description"><?php print $question->description; ?></div>
                        <?php if (!isset($publicInterface)) : ?>
                            <div class="label"><strong><?php print $question->getNomUrl(1, '', 1, '', -1, 1); ?></strong></div>
                        <?php else : ?>
                            <div class="label"><strong><?php print $question->label; ?></strong></div>
                        <?php endif; ?>
                        <div class="question-comment-container">
                            <div class="question-ref">
                                <?php
                                if (!empty($objectLine->ref && !isset($publicInterface)) ) {
                                   print '<span class="question-ref-title">' . $objectLine->ref . '</span> :';
                                } ?>
                            </div>
                            <?php if ($question->type == 'Text') : ?>
                            <div class="question-answer-text">
                                <?php
                                $object->status > $object::STATUS_DRAFT ? print $questionAnswer :
                                    print '<textarea' . ($object->status > $object::STATUS_DRAFT ? ' disabled' : '') . ' name="answer' . $question->id . '" id="answer' . $question->id . '"class="question-textarea input-answer ' . ($object->status > 0 ? 'disable' : '') . '" value="' . $questionAnswer . '"></textarea>'; ?>
                            <?php endif; ?>
                            <?php if ($question->enter_comment > 0) : ?>
                                <?php print $langs->trans('Comment') . ' : '; ?>
                            <?php endif; ?>
                            <?php if ($question->enter_comment > 0) : ?>
                                <?php if ($object->status > 0 ) : ?>
                                    <?php print $comment; ?>
                                <?php else : ?>
                                    <?php print '<input class="question-textarea question-comment" name="comment' . $question->id . '" id="comment' . $question->id . '" value="' . $comment .  '" ' . ($object->status == 2 ? 'disabled' : '') . '>'; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($question->show_photo > 0 && getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . '_DISPLAY_MEDIAS') && !empty($user->conf->DIGIQUALI_SHOW_OK_KO_PHOTOS)) : ?>
                        <!-- Photo OK KO -->
                        <div class="table-cell table-450 cell-photo-check wpeo-table">
                            <?php
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ok', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ok', $question, 'photo_ok', 0, 0, 0,1, 'photo-ok', 0);
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ko', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ko', $question, 'photo_ko', 0, 0, 0,1, 'photo-ko', 0);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="table-row <?php echo ($conf->browser->layout != 'classic' ? 'center' : ''); ?>">
                    <!-- Galerie -->
                    <?php if ($question->authorize_answer_photo > 0) : ?>
                        <div class="table-cell table-full linked-medias answer_photo_<?php echo $question->id ?>">
                            <?php if ($object->status == 0 ) : ?>
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
                            <?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 1, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
                        </div>
                    <?php endif; ?>
                    <?php $pictosArray = get_answer_pictos_array(); ?>
                    <?php if ($question->type == 'MultipleChoices') :
                        $answerList = $answer->fetchAll('ASC', 'position', 0, 0,  ['customsql' => 't.status > ' . Answer::STATUS_DELETED . ' AND t.fk_question = ' . $question->id]); ?>
                        <div class="table-cell table-end select-answer answer-cell" <?php echo ($object->status > 0) ? ' style="pointer-events: none"' : '' ?> data-questionId="<?php echo $question->id; ?>">
                            <?php
                            if (preg_match('/,/', $questionAnswer)) {
                                $questionAnswers = preg_split('/,/', $questionAnswer);
                            } else {
                                $questionAnswers = [$questionAnswer];
                            }

                            print '<input type="hidden" class="question-answer" name="answer' . $question->id . '" id="answer' . $question->id . '" value="0">';
                            if (is_array($answerList) && !empty($answerList)) {
                                foreach($answerList as $answerLinked) {
                                    print '<input type="hidden" class="answer-color answer-color-' . $answerLinked->position . '" value="' . $answerLinked->color . '">';
                                    print '<span style="' . (in_array($answerLinked->position, $questionAnswers) ? 'background:' . $answerLinked->color .'; ' : '') . 'color:' . $answerLinked->color . ';" class="answer multiple-answers square ' . ($object->status > 0 ? 'disable' : '') . ' ' . (in_array($answerLinked->position, $questionAnswers) ? 'active' : '') . '" value="' . $answerLinked->position . '">';
                                    if (!empty($answerLinked->pictogram)) {
                                        print $pictosArray[$answerLinked->pictogram]['picto_source'];
                                    } else {
                                        print $answerLinked->value;
                                    }
                                    print '</span>';
                                }
                            }
                            ?>
                        </div>
                    <?php elseif ($question->type == 'UniqueChoice' || $question->type == 'OkKo' || $question->type == 'OkKoToFixNonApplicable') :
                        $answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status > ' . Answer::STATUS_DELETED . ' AND t.fk_question = ' . $question->id]); ?>
                        <div class="table-cell table-end select-answer answer-cell table-300" <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?> data-questionId="<?php echo $question->id; ?>">
                            <?php
                            print '<input type="hidden" class="question-answer" name="answer' . $question->id . '" id="answer' . $question->id . '" value="0">';
                            if (is_array($answerList) && !empty($answerList)) {
                                foreach($answerList as $answerLinked) {
                                    print '<input type="hidden" class="answer-color answer-color-' . $answerLinked->position . '" value="' . $answerLinked->color . '">';
                                    print '<span style="' . ($questionAnswer == $answerLinked->position ? 'background:' . $answerLinked->color . '; ' : '') . 'color:' . $answerLinked->color . ';" class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="' . $answerLinked->position . '">';
                                    if (!empty($answerLinked->pictogram)) {
                                        print $pictosArray[$answerLinked->pictogram]['picto_source'];
                                    } else {
                                        print $answerLinked->value;
                                    }
                                    print '</span>';
                                }
                            } ?>
                        </div>
                    <?php elseif ($question->type == 'Percentage') : ?>
                        <div class="table-cell answer-cell table-flex table-full percentage-cell <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>" data-questionId="<?php echo $question->id; ?>">
                            <?php
                            print img_picto('', 'fontawesome_fa-frown_fas_#D53C3D_3em', 'class="range-image"');
                            print '<input type="range" class="search_component_input range input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" name="answer' . $question->id . '" id="answer' . $question->id . '" min="0" max="100" step="25" value="' . $questionAnswer . '"' . ($object->status > $object::STATUS_DRAFT ? ' disabled' : '') . '>';
                            print img_picto('', 'fontawesome_fa-grin_fas_#57AD39_3em', 'class="range-image"');
                            ?>
                        </div>
                    <?php elseif ($question->type == 'Range') : ?>
                        <div class="table-cell table-end answer-cell table-flex <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>" data-questionId="<?php echo $question->id; ?>">
                            <?php
                            print '<span class="table-cell" value="">';
                            print $langs->transnoentities('Answer') . ' : ';
                            print '</span>';
                            print '<span class="table-cell" value="">';
                            print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer' . $question->id . '" id="answer' . $question->id . '" type="number" class="input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="' . $questionAnswer . '">';
                            print '</span>';
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <div class="question__header-medias">
                    <?php if ($question->show_photo > 0 && getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . '_DISPLAY_MEDIAS')) : ?>
                        <?php
                        print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ok', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ok', $question, 'photo_ok', 0, 0, 0,1, 'photo-ok', 0);
                        print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $question->ref . '/photo_ko', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ko', $question, 'photo_ko', 0, 0, 0,1, 'photo-ko', 0);
                        ?>
                    <?php endif; ?>
                </div>
                <div class="question__container">
                    <div class="question__header">
                        <div class="question__header-content">
                            <div class="question-title">Titre de la question</div>
                            <div class="question-description">Description de la question Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis tempor pulvinar eros, quis placerat nibh aliquam et. Morbi dolor massa, tincidunt tempor</div>
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
                        <div class="wpeo-button button-square-50 add-action modal-open" value="<?php echo $objectLine->id ?>">
                            <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add<?php echo $objectLine->id?>" data-from-id="<?php echo $objectLine->id ?>" data-from-type="<?php echo $object->element ?>" data-from-subtype="answer_photo_<?php echo $question->id ?>" data-from-subdir="answer_photo/<?php echo $question->ref ?>"/>
                            <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>
                        </div>

                        <div class="wpeo-modal" id="answer_task_add<?php echo $objectLine->id?>" data-project-id="<?php echo $project->id ?: '' ?>" data-line-id="<?php echo $objectLine->id ?>">
                            <div class="modal-container wpeo-modal-event">
                                <!-- Modal-Header -->
                                <div class="modal-header">
                                    <h2 class="modal-title"><?php echo $langs->trans('TaskCreate') . ' ' . $taskNextValue . (!empty($project->id) ? '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl() : '') ?></h2>
                                    <div class="modal-close"><i class="fas fa-times"></i></div>
                                </div>
                                <div class="modal-content" id="#modalContent<?php echo $objectLine->id ?>">
                                    <div class="messageWarningTaskLabel notice hidden">
                                        <div class="wpeo-notice notice-warning">
                                            <div class="notice-content">
                                                <div class="notice-title"><?php echo $langs->trans('Label') ?></div>
                                            </div>
                                            <div class="notice-close"><i class="fas fa-times"></i></div>
                                        </div>
                                    </div>
                                    <div class="answer-task-container">
                                        <div class="answer-task">
                                            <span class="title"><?php echo $langs->trans('Label'); ?></span>
                                            <input type="text" class="answer-task-label" name="label" value="">
                                            <div class="answer-task-date wpeo-gridlayout grid-2">
                                                <div>
                                                    <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                                                    <input type="datetime-local" class="answer-task-date-start" name="date_start">'
                                                </div>
                                                <div>
                                                    <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                                                    <input type="datetime-local" class="answer-task-date-start" name="date_end">'
                                                </div>
                                            </div>
                                            <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                                            <input type="text" class="answer-task-budget" name="budget" value="">
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal-Footer -->
                                <div class="modal-footer">
                                    <?php if ($permissiontoaddtask) : ?>
                                        <div class="wpeo-button answer-task-create button-blue button-disable modal-close" value="<?php echo $objectLine->id ?>">
                                            <i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
                                            <i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="question__list-medias">Liste des photos</div>
                    <div class="question__list-actions" id="answer_task_list<?php echo $objectLine->id ?>">
                        <?php foreach ($objectLine->linkedObjects['project_task'] ?? [] as $relatedTask) : ?>
                            <?php
                                $allTimeSpentArray = $task->fetchAllTimeSpentAllUsers(' AND task_id = ' . $relatedTask->id);
                                $allTimeSpent = 0;
                                if (is_array($allTimeSpentArray) && !empty($allTimeSpentArray)) {
                                    foreach ($allTimeSpentArray as $timespent) {
                                        $allTimeSpent += $timespent->timespent_duration / 60;
                                    }
                                }
                            ?>
                            <div class="question__action">
                                <div class="question__action-check"><input type="checkbox" <?php echo ($relatedTask->progress == 100 ? 'checked' : ''); ?> data-task-id="<?php echo $relatedTask->id; ?>"/></div>
                                <div class="question__action-body">
                                    <div class="question__action-metas">
                                        <span class="question__action-metas-ref"><?php echo $relatedTask->ref ?></span>
                                        <span class="question__action-metas-author"></span>
                                        <span class="question__action-metas-date"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($relatedTask->dateo))) ? $relatedTask->dateo : $relatedTask->datec)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($relatedTask->datee))) ? ' - ' . date('d/m/Y', $relatedTask->datee) : ''); ?></span>
                                        <span class="question__action-metas-time"><i class="fas fa-clock"></i> <?php echo $allTimeSpent . '/' . $relatedTask->planned_workload ?></span>
                                        <span class="question__action-metas-budget"><i class="fas fa-coins"></i> <?php echo price($relatedTask->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
                                    </div>
                                    <div class="question__action-content"><?php echo $relatedTask->label ?></div>
                                </div>
                                <div class="question__action-buttons">
                                    <div class="wpeo-button button-square-40 button-transparent modal-open">
                                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_edit<?php echo $relatedTask->id ?>" data-from-id="<?php echo $relatedTask->id ?>" data-from-type="answertask">
                                        <i class="fas fa-pencil-alt button-icon"></i>
                                    </div>
                                    <div class="wpeo-button button-square-40 button-transparent delete-task" data-message="<?php echo $langs->transnoentities('DeleteTask') . ' ' . $relatedTask->ref;  ?>" data-task-id="<?php echo $relatedTask->id ?>"><i class="fas fa-trash button-icon"></i></div>
                                </div>

                                <div class="wpeo-modal answer-task-edit-modal" id="answer_task_edit<?php echo $relatedTask->id ?>" data-task-id="<?php echo $relatedTask->id ?>" data-line-id="<?php echo $objectLine->id ?>">
                                    <div class="modal-container wpeo-modal-event">
                                        <!-- Modal-Header -->
                                        <div class="modal-header">
                                            <h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $relatedTask->getNomUrl(0) ?></h2>
                                            <div class="modal-close"><i class="fas fa-times"></i></div>
                                        </div>
                                        <!-- Modal-Content -->
                                        <div class="modal-content answer-task-content">
                                            <div id="task-data<?php echo $relatedTask->id ?>">
                                                <span class="answer-task-reference" value="<?php echo $relatedTask->ref ?>"><?php echo $relatedTask->getNomUrl(0, 'withproject'); ?></span>
                                                <span class="answer-task-author"><?php $userAuthor = $usersList[$relatedTask->fk_user_creat > 0 ? $relatedTask->fk_user_creat : $user->id];  //echo getNomUrlUser($userAuthor); ?>
								                </span>
                                                <span class="answer-task-date">
									                <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($relatedTask->dateo))) ? $relatedTask->dateo : $relatedTask->datec)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($relatedTask->datee))) ? ' - ' . date('d/m/Y', $relatedTask->datee) : ''); ?>
								                </span>
                                                <span class="answer-total-task-timespent answer-total-task-timespent-<?php echo $relatedTask->id ?>">
									                <i class="fas fa-clock"></i> <?php echo $allTimeSpent . '/' . $relatedTask->planned_workload/60 ?>
								                </span>
                                                <span><i class="fas fa-coins"></i> <?php echo price($relatedTask->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
                                                <span class="answer-task-progress <?php //echo $relatedTask->getTaskProgressColorClass($task_progress); ?>"><?php echo $relatedTask->progress ? $relatedTask->progress . " %" : 0 . " %" ?></span>
                                            </div>
                                            <br>
                                            <div class="answer-task-content">
                                                <!--								<span class="title">--><?php //echo $langs->trans('Label'); ?><!--</span>-->
                                                <div class="answer-task-title">
                                                    <?php if (!$conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) : ?>
                                                    <span class="answer-task-progress-checkbox">
                                                        <input type="checkbox" id="" class="answer-task-progress-checkbox" name="progress-checkbox" value="" <?php echo ($relatedTask->progress == 100) ? 'checked' : ''; ?>>
                                                    </span>
                                                    <?php endif; ?>
                                                    <input type="text" class="answer-task-author-label answer-task-label" name="label" value="<?php echo $relatedTask->label; ?>">
                                                </div>
                                                <div class="answer-task-date wpeo-gridlayout grid-3">
                                                    <div>
                                                        <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                                                        <?php print '<input type="datetime-local" class="answer-task-start-date" name="answerTaskDateStartEdit' . $relatedTask->id . '" value="' . ($relatedTask->dateo ? dol_print_date($relatedTask->dateo, '%Y-%m-%dT%H:%M:%S') : '') . '">'; ?>
                                                    </div>
                                                    <div>
                                                        <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                                                        <?php print '<input type="datetime-local" class="answer-task-end-date" name="answerTaskDateEndEdit' . $relatedTask->id . '" value="' . ($relatedTask->datee ? dol_print_date($relatedTask->datee, '%Y-%m-%dT%H:%M:%S') : '') . '">'; ?>
                                                    </div>
                                                    <div>
                                                        <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                                                        <input type="text" class="answer-task-budget" name="budget" value="<?php echo price2num($relatedTask->budget_amount); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <!-- answer TASK TIME SPENT NOTICE -->
                                            <div class="messageSuccessTaskTimeSpentCreate<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForCreateTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForCreateTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenCreatedM') ?>">
                                                <div class="wpeo-notice notice-success answer-task-timespent-create-success-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellCreated') ?></div>
                                                        <div class="notice-subtitle">
                                                            <span class="text"></span>
                                                        </div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="messageErrorTaskTimeSpentCreate<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForCreateTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForCreateTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenCreateM') ?>">
                                                <div class="wpeo-notice notice-warning answer-task-timespent-create-error-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotCreated') ?></div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="messageSuccessTaskTimeSpentEdit<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenEditedM') ?>">
                                                <div class="wpeo-notice notice-success answer-task-timespent-edit-success-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellEdited') ?></div>
                                                        <div class="notice-subtitle">
                                                            <span class="text"></span>
                                                        </div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="messageErrorTaskTimeSpentEdit<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedM') ?>">
                                                <div class="wpeo-notice notice-warning answer-task-timespent-edit-error-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotEdited') ?></div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="messageSuccessTaskTimeSpentDelete<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedM') ?>">
                                                <div class="wpeo-notice notice-success answer-task-timespent-delete-success-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellDeleted') ?></div>
                                                        <div class="notice-subtitle">
                                                            <span class="text"></span>
                                                        </div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="messageErrorTaskTimeSpentDelete<?php echo $relatedTask->id ?> notice hidden">
                                                <input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
                                                <input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedM') ?>">
                                                <div class="wpeo-notice notice-warning answer-task-timespent-delete-error-notice">
                                                    <div class="notice-content">
                                                        <div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotDeleted') ?></div>
                                                    </div>
                                                    <div class="notice-close"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                            <div class="answer-task-timespent-container">
                                                <span class="title"><?php echo $langs->trans('TimeSpent'); ?></span>
                                                <div class="answer-task-timespent-add-container">
                                                    <div class="timespent-date">
                                                        <span class="title"><?php echo $langs->trans('Date'); ?></span>
                                                        <?php print $form->selectDate(dol_now('tzuser'), 'answer-task-timespent-date', 1, 1, 0, 'answer_task_timespent_form', 1, 0); ?>
                                                    </div>
                                                    <div class="timespent-comment">
                                                        <span class="title"><?php echo $langs->trans('Comment'); ?></span>
                                                        <input type="text" class="answer-task-timespent-comment" name="comment" value="">
                                                    </div>
                                                    <div class="timespent-duration">
                                                        <span class="title"><?php echo $langs->trans('Duration'); ?></span>
                                                        <span class="time"><?php print '<input type="number" placeholder="minutes" class="answer-task-timespent-duration" name="timespentDuration" value="'.$conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION.'">'; ?></span>
                                                    </div>
                                                    <?php if ($permissiontoadd) : ?>
                                                        <div class="timespent-add-button">
                                                            <div class="wpeo-button answer-task-timespent-create button-square-30 button-rounded" data-task-id="<?php echo $relatedTask->id ?>">
                                                                <i class="fas fa-plus button-icon"></i>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div id="answer-task-timespent-list<?php echo $relatedTask->id ?>">
                                                    <ul class="wpeo-table table-flex">
                                                        <?php if (!empty($allTimeSpentArray) && $allTimeSpentArray > 0) : ?>
                                                            <?php foreach ($allTimeSpentArray as $time_spent) :?>
                                                                <li class="answer-task-timespent-<?php echo $time_spent->timespent_id ?>">
                                                                    <input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTaskTimeSpent', $time_spent->timespent_duration/60) . ' ' . $relatedTask->ref . ' ?'; ?>">
                                                                    <div class="table-row answer-task-timespent-container">
                                                                        <div class="table-cell table-padding-0 answer-task-timespent-single">
															<span class="answer-task-timespent-author">
																<?php $userAuthor = $usersList[$time_spent->timespent_fk_user?:$user->id];
                                                                //echo getNomUrlUser($userAuthor); ?>
															</span>
                                                                            <span class="answer-task-timespent-date">
																<i class="fas fa-calendar-alt"></i> <?php echo dol_print_date($time_spent->timespent_datehour, 'dayhour'); ?>
															</span>
                                                                            <span class="answer-task-timespent-time">
																<i class="fas fa-clock"></i> <?php echo $time_spent->timespent_duration/60 . ' mins'; ?>
															</span>
                                                                            <span class="answer-task-timespent-comment">
																<?php echo $time_spent->timespent_note; ?>
															</span>
                                                                        </div>
                                                                        <!-- BUTTON MODAL TASK TIMESPENT EDIT  -->
                                                                        <div class="table-cell table-end table-125 table-padding-0 answer-task-actions">
                                                                            <?php if ($permissiontoadd) : ?>
                                                                                <div class="wpeo-button button-square-50 button-transparent modal-open" value="<?php echo $time_spent->timespent_id ?>">
                                                                                    <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_edit<?php echo $time_spent->timespent_id; ?>" data-from-id="<?php echo $time_spent->timespent_id; ?>" data-from-type="answertasktimespent" data-from-subtype="photo" data-from-subdir="" data-photo-class="answer-from-answer-create-<?php echo $risk->id; ?>"/>
                                                                                    <i class="fas fa-pencil-alt button-icon"></i>
                                                                                </div>
                                                                            <?php else : ?>
                                                                                <div class="wpeo-button button-square-50 button-transparent wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $time_spent->timespent_id ?>">
                                                                                    <i class="fas fa-pencil-alt button-icon"></i>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <?php if ($permissiontodelete) : ?>
                                                                                <div class="answer-task-timespent-delete wpeo-button button-square-50 button-transparent" data-timespent-id="<?php echo $time_spent->timespent_id; ?>">
                                                                                    <i class="fas fa-trash button-icon"></i>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </li>

                                                                <div class="wpeo-modal modal-answer-task-timespent" id="answer_task_timespent_edit<?php echo $time_spent->timespent_id ?>">
                                                                    <div class="modal-container wpeo-modal-event">
                                                                        <!-- Modal-Header -->
                                                                        <div class="modal-header">
                                                                            <h2 class="modal-title"><?php echo $langs->trans('TaskTimeSpentEdit') . ' ' . $relatedTask->getNomUrl(0, 'withproject') ?></h2>
                                                                            <div class="modal-close"><i class="fas fa-times"></i></div>
                                                                        </div>
                                                                        <!-- Modal EDIT RISK ASSESSMENT TASK Content-->
                                                                        <div class="modal-content" id="#modalContent<?php echo $time_spent->timespent_id ?>">
                                                                            <div class="answer-task-timespent-container" value="<?php echo $relatedTask->id; ?>">
                                                                                <div class="answer-task-timespent-edit">
                                                                                    <span class="title"><?php echo $langs->trans('TimeSpent'); ?></span>
                                                                                    <span class="title"><?php echo $langs->trans('Date'); ?></span>
                                                                                    <?php print $form->selectDate($time_spent->timespent_datehour, 'answerTaskTimespentDateEdit'.$time_spent->timespent_id, 1, 1, 0, 'answer_task_timespent_form', 1, 0); ?>
                                                                                    <span class="title"><?php echo $langs->trans('Comment'); ?> <input type="text" class="answer-task-timespent-comment" name="comment" value="<?php echo $time_spent->timespent_note; ?>"></span>
                                                                                    <span class="title"><?php echo $langs->trans('Duration'); ?></span>
                                                                                    <span class="time"><?php print '<input type="number" placeholder="minutes" class="answer-task-timespent-duration" name="timespentDuration" value="'.($time_spent->timespent_duration/60).'">'; ?></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <!-- Modal-Footer -->
                                                                        <div class="modal-footer">
                                                                            <?php if ($permissiontoadd) : ?>
                                                                                <div class="wpeo-button answer-task-timespent-save button-green" value="<?php echo $time_spent->timespent_id ?>">
                                                                                    <i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
                                                                                </div>
                                                                            <?php else : ?>
                                                                                <div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
                                                                                    <i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
                                                                                </div>
                                                                            <?php endif;?>
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Modal-Footer -->
                                        <div class="modal-footer">
                                            <?php if ($permissiontoaddtask) : ?>
                                                <div class="wpeo-button answer-task-save button-green" data-task-id="<?php echo $relatedTask->id ?>" data-line-id="<?php echo $objectLine->id ?>">
                                                    <i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
                                                    <i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
                                                </div>
                                            <?php endif;?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <?php
        }
    }
}
