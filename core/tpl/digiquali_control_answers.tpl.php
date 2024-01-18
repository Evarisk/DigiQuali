<?php
// Lines
if (is_array($questionIds) && !empty($questionIds)) {
	foreach ($questionIds as $questionId) {
		$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
		$questionAnswer = '';
		$comment = '';
		if ($result > 0 && is_array($result)) {
			$itemControlDet = array_shift($result);
			$questionAnswer = $itemControlDet->answer;
			$comment = $itemControlDet->comment;
		}
        if (!$user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER or empty($questionAnswer)) {
            $item = $question;
            $item->fetch($questionId);
            ?>
            <div class="wpeo-table table-flex table-3 table-id-<?php echo $item->id ?>" data-publicInterface="<?php echo $publicInterface; ?>" data-autoSave="<?php echo getDolGlobalInt('DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION'); ?>">
                <div class="table-row">
                    <!-- Contenu et commentaire -->
                    <div class="table-cell table-full">
                        <div class="label"><strong><?php print $item->getNomUrl(1, isset($publicInterface) ? 'nolink' : '', 1, '', -1, 1); ?></strong></div>
                        <div class="description"><?php print $item->description; ?></div>
                        <div class="question-comment-container">
                            <div class="question-ref">
                                <?php
                                if ( ! empty( $itemControlDet->ref ) ) {
                                    print '<span class="question-ref-title">' . $itemControlDet->ref . '</span> :';
                                } ?>
                            </div>
                            <?php if ($item->type == 'Text') : ?>
                                <div class="<?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
                                    <?php
                                    print '<span>' . $langs->trans('Answer') . ' : </span>';
                                    $object->status > $object::STATUS_DRAFT ? print $questionAnswer :
                                        print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'"class="question-textarea input-answer ' . ($object->status > 0 ? 'disable' : '') . '" value="'. $questionAnswer .'">';
                                    ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($item->enter_comment > 0) : ?>
                                <?php print $langs->trans('Comment') . ' : '; ?>
                            <?php endif; ?>
                            <?php if ($item->enter_comment > 0) : ?>
                                <?php if ($object->status > 0 ) : ?>
                                    <?php print $comment; ?>
                                <?php else : ?>
                                    <?php print '<input class="question-textarea question-comment" name="comment'. $item->id .'" id="comment'. $item->id .'" value="'. $comment .'" '. ($object->status == 2 ? 'disabled' : '').'>'; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Photo OK KO -->
                    <?php if ($item->show_photo > 0) : ?>
                        <div class="table-cell table-450 cell-photo-check wpeo-table">
                            <?php
                            if (!empty($conf->global->DIGIQUALI_CONTROL_DISPLAY_MEDIAS)) :
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ok', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, 'question/'. $item->ref . '/photo_ok', $item, 'photo_ok', 0, 0, 0,1, 'photo-ok', 0);
                                print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ko', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, 'question/'. $item->ref . '/photo_ko', $item, 'photo_ko', 0, 0, 0,1, 'photo-ko', 0);
                            endif;
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="table-row <?php echo ($onPhone ? 'center' : ''); ?>">
                    <!-- Galerie -->
                    <?php if ($item->authorize_answer_photo > 0) : ?>
                        <div class="table-cell table-full linked-medias answer_photo_<?php echo $item->id ?>">
                            <?php if ($object->status == 0 ) : ?>
                                <input hidden multiple class="fast-upload" id="fast-upload-answer-photo<?php echo $item->id ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
                                <input type="hidden" class="question-answer-photo" id="answer_photo_<?php echo $item->id ?>" name="answer_photo_<?php echo $item->id ?>" value=""/>
                                <label for="fast-upload-answer-photo<?php echo $item->id ?>">
                                    <div class="wpeo-button button-square-50">
                                        <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                                    </div>
                                </label>
                                <div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="<?php echo $item->id ?>">
                                    <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id ?>" data-from-type="<?php echo $object->element ?>" data-from-subtype="answer_photo_<?php echo $item->id ?>" data-from-subdir="answer_photo/<?php echo $item->ref ?>"/>
                                    <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
                                </div>
                            <?php endif; ?>
                            <?php $relativepath = 'digiquali/medias/thumbs';
                            print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/'. $object->ref . '/answer_photo/' . $item->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'control/'. $object->ref . '/answer_photo/' . $item->ref, $item, '', 0, $object->status == 0, 1);
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $pictosArray = get_answer_pictos_array();
                    ?>
                    <?php if ($item->type == 'MultipleChoices') :
                        $answerList = $answer->fetchAll('ASC', 'position', 0, 0,  ['customsql' => 't.status > ' . Answer::STATUS_DELETED . ' AND t.fk_question = ' . $item->id]);
                        ?>
                        <div class="table-cell table-end select-answer answer-cell" <?php echo ($object->status > 0) ? ' style="pointer-events: none"' : '' ?> data-questionId="<?php echo $item->id; ?>">
                            <?php
                            if (preg_match('/,/', $questionAnswer)) {
                                $questionAnswers = preg_split('/,/', $questionAnswer);
                            } else {
                                $questionAnswers = [$questionAnswer];
                            }

                            print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';
                            if (is_array($answerList) && !empty($answerList)) {
                                foreach($answerList as $answerLinked) {
                                    print '<input type="hidden" class="answer-color answer-color-'. $answerLinked->position .'" value="'. $answerLinked->color .'">';
                                    print '<span style="'. (in_array($answerLinked->position, $questionAnswers) ? 'background:'. $answerLinked->color .'; ' : '') .'color:'. $answerLinked->color .';" class="answer multiple-answers square ' . ($object->status > 0 ? 'disable' : '') . ' ' . (in_array($answerLinked->position, $questionAnswers) ? 'active' : '') . '" value="'. $answerLinked->position .'">';
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
                    <?php elseif ($item->type == 'UniqueChoice' || $item->type == 'OkKo' || $item->type == 'OkKoToFixNonApplicable') :
                        $answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status > ' . Answer::STATUS_DELETED . ' AND t.fk_question = ' . $item->id]);
                        ?>
                        <div class="table-cell table-end select-answer answer-cell table-300" <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?> data-questionId="<?php echo $item->id; ?>">
                            <?php
                            print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';
                            if (is_array($answerList) && !empty($answerList)) {
                                foreach($answerList as $answerLinked) {
                                    print '<input type="hidden" class="answer-color answer-color-'. $answerLinked->position .'" value="'. $answerLinked->color .'">';
                                    print '<span style="'. ($questionAnswer == $answerLinked->position ? 'background:'. $answerLinked->color .'; ' : '') .'color:'. $answerLinked->color .';" class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $answerLinked->position .'">';
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
                    <?php elseif ($item->type == 'Percentage') : ?>
                        <div class="table-cell table-end answer-cell table-flex <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>" data-questionId="<?php echo $item->id; ?>">
                            <?php
                            print '<span class="table-cell" value="">';
                            print $langs->transnoentities('Answer') . ' : ';
                            print '</span>';
                            print '<span class="table-cell" value="">'; ?>
                            <?php print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'" type="number" min="0" max="100" onkeyup=window.saturne.utils.enforceMinMax(this) class="input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $questionAnswer .'"> %';
                            print '</span>';
                            ?>
                        </div>
                    <?php elseif ($item->type == 'Range') : ?>
                        <div class="table-cell table-end answer-cell table-flex <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>" data-questionId="<?php echo $item->id; ?>">
                            <?php
                            print '<span class="table-cell" value="">';
                            print $langs->transnoentities('Answer') . ' : ';
                            print '</span>';
                            print '<span class="table-cell" value="">';
                            print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'" type="number" class="input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $questionAnswer .'">';
                            print '</span>';
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
	}
}
