<?php

/**
 * \file    digiquali_mass_control_list.tpl.php
 * \ingroup digiquali
 * \brief   Template for displaying the list of mass controls linked to an object
 */

// Fetch the list of mass controls linked to the object
$subControlList = $object->fetchAll('', '', 0, 0, ['fk_control' => $object->id]);
// Start the responsive table container
print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';

// Load and print the title for the control list section
print load_fiche_titre($langs->trans('LinkedControlList'), '', '');

// Start the table
print '<div class="wpeo-table table-flex table-3">';

// Define table headers with appropriate translations
$tableHeaders = [
    $langs->trans('Name'),
    $langs->trans('Status'),
    $langs->trans('ControlledObject'),
    $langs->trans('Verdict'),
    $langs->trans('NoteControl'),
    $langs->trans('Answers'),
    $langs->trans('Document'),
    $langs->trans('Action'),
];

// Create header row using divs
print '<div class="table-row header-row">';
$i = 0;
foreach ($tableHeaders as $header) {
    print '<div class="table-cell header-cell '. ($i >= 2 ? 'center' : '').'">' . $header . '</div>';
    $i++;
}
print '</div>';

$mainControlId = $object->id;
$sheet = new Sheet($db);
$mainControl = $object;

// Check if there are any mass controls and print them
if (is_array($subControlList) && !empty($subControlList)) {
    foreach ($subControlList as $subControl) {
        $answersDisabled = $subControl->status == $subControl::STATUS_LOCKED || $mainControl->status >= $mainControl::STATUS_VALIDATED;
        $object = $subControl;
        $sheet->fetch($subControl->fk_sheet);
        $sheet->fetch_optionals();

        $sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_' . $sheet->element);
        $subControl->fetch_optionals();
        $subControl->fetchLines();
        $subControl->fetchObjectLinked('', '', $subControl->id, 'digiquali_control', 'OR', 1, 'sourcetype', 0);
        //get object controlled
        $linkableElements = get_sheet_linkable_objects();

        print '<div class="table-row sub-control-'. $subControl->id .'">';
        print '<div class="table-cell">' . $subControl->getNomUrl(1) . '</div>';
        print '<div class="table-cell">' . $subControl->getLibStatut(5) . '</div>';
        print '<div class="table-cell maxwidth200">';
        foreach ($linkableElements as $linkableElementType => $linkableElement) {
            if ($linkableElement['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableElement['link_name']]))) {
                $className    = $linkableElement['className'];
                $linkedObject = new $className($db);
                foreach($object->linkedObjectsIds[$linkableElement['link_name']] as $linkedObjectId) {
                    $linkedObject->fetch($linkedObjectId);


                    print $linkedObject->getNomUrl(1, 0, '', 'maxwidth200');

                    if ($linkedObject->array_options['options_qc_frequency'] > 0) {
                        print ' ';
                        print '<strong>';
                        print $langs->transnoentities('QcFrequency') . ' : ' . $linkedObject->array_options['options_qc_frequency'];
                        print '</strong>';
                    }

                    print '<br/>';
                }
            }
        }
        print '</div>';

        // Verdict section with interactive OK/KO buttons
        print '<div class="table-cell center">';
        print '<div class="verdict-container">';
        print '<label class="verdict-option">';
        print '<input type="radio" name="verdict' . $subControl->id . '" value="1" ' . ($subControl->verdict == '1' ? 'checked' : '') . '>';
        print '<span class="verdict-box verdict-ok '. ($answersDisabled ? "disabled" : "") .'" data-control-id="'. $subControl->id .'">OK</span>';
        print '</label>';
        print '<label class="verdict-option">';
        print '<input data-control-id="'. $subControl->id .'" type="radio" name="verdict' . $subControl->id . '" value="0" ' . ($subControl->verdict == '0' ? 'checked' : '') . '>';
        print '<span class="verdict-box verdict-ko '. ($answersDisabled ? "disabled" : "") .'" data-control-id="'. $subControl->id .'">KO</span>';
        print '</label>';
        print '</div>';
        print '</div>';

        // Note Control section displaying the public note
        print '<div class="table-cell center"><textarea '. ($answersDisabled ? "disabled" : "") .' type="text" class="note-public">' . $subControl->note_public . '</textarea></div>';

        print '<div class="table-cell center">';
        $questionCounter = 0;
        if (!empty($sheet->linkedObjects['digiquali_question'])) {
            $questionCounter = count($sheet->linkedObjects['digiquali_question']);
        }

        $answerCounter = 0;
        if (is_array($subControl->lines) && !empty($subControl->lines)) {
            foreach ($subControl->lines as $subControlLine) {
                if (dol_strlen($subControlLine->answer) > 0) {
                    $answerCounter++;
                }
            }
        }
        //affiche le nombre de questions répondues
        print '<span class="answerCounter">' . $answerCounter . '/' . $questionCounter . '</span>';
        print '<button type="button" class="'. ($answersDisabled ? "butActionRefused" : "butAction modal-open") .' answerSubControl" data-control-id="'. $subControl->id .'">';
        print $langs->trans('Answers');
        print '<input type="hidden" class="modal-options" data-modal-to-open="modalSubControl'. $subControl->id .'">';
        print '</button>';
        print '</div>';
        $documenturl = DOL_URL_ROOT . '/document.php';
        //retrieve last document of the control
        print '<div class="table-cell center">';
        $documentList = dol_dir_list($conf->digiquali->multidir_output[$subControl->entity ?: 1] . '/controldocument/' . $subControl->ref . '/');
        if (!empty($documentList)) {
            $lastDocument = $documentList[count($documentList) - 1];
            $lastDocumentPath = $lastDocument['relativename'];
            print '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=digiquali&file=controldocument/' . urlencode($subControl->ref . '/' . $lastDocumentPath) . '">';
            print '<button type="button" class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" aria-label="' . $langs->trans('ShowDocument') . '"><i class="fas fa-eye button-icon"></i></button>';
            print '</a>';
        }

        print '</div>';
        print '<div class="table-cell center">';
        if (!$answersDisabled) {
            if ($subControl->status == $subControl::STATUS_VALIDATED) {
                $displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
                print '<span class="lockSubControl butAction" id="actionButtonLockSubControl" data-control-id="'. $subControl->id .'" data-main-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
                $displayButton = $onPhone ? '<i class="fas fa-unlock fa-2x"></i>' : '<i class="fas fa-unlock"></i>' . ' ' . $langs->trans('ReOpenDoli');
                print '<span class="reopenSubControl butAction" id="actionButtonReopenSubControl" data-control-id="'. $subControl->id .'" data-main-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
            } else {
                $validateButtonDisabled = !(dol_strlen($subControl->verdict) && $answerCounter == $questionCounter);
                $displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
                print '<span class="validateSubControl validateButton'. $subControl->id .' butAction'. ($validateButtonDisabled ? 'Refused' : '') .'" id="actionButtonValidateSubControl" data-control-id="'. $subControl->id .'" data-main-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
                $displayButton = $onPhone ? '<i class="fas fa-save fa-2x"></i>' : '<i class="fas fa-save"></i>' . ' ' . $langs->trans('Save');
                print '<span class="saveSubControl butAction'. (!$validateButtonDisabled ? 'Refused' : '') .'" id="saveButton'. $subControl->id .'" data-control-id="'. $subControl->id .'" data-main-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
            }
        } else if ($subControl->status != $subControl::STATUS_LOCKED) {
            print $langs->trans('MainControlMustBeDraftToEditSubControls');
        } else {
            print '';
        }

        print '</div>';

        print '<div class="wpeo-modal" id="modalSubControl'. $subControl->id .'">';
        print '<div class="modal-container">';
        print '<div class="modal-content">';
        print load_fiche_titre($langs->trans('LinkedQuestionsList') . ' - ' . $subControl->getNomUrl(1), '', '');
        $conf->global->DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION = 0;
        print '<div id="tablelines" class="question-answer-container noborder noshadow">';
        require __DIR__ . '/../../core/tpl/digiquali_answers.tpl.php';
        print '</div>';
        print '</div>';
        print '<div class="modal-footer">';
        $displayButton = $onPhone ? '<i class="fas fa-save fa-2x"></i>' : '<i class="fas fa-save"></i>' . ' ' . $langs->trans('Save');
        print '<span class="saveSubControlAnswers butAction" id="actionButtonSaveSubControlAnswer" data-control-id="'. $subControl->id .'" data-main-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
        print '</div>';
        print '</div>';
        print '</div>';
        print '</div>';
    }
} else {
    // If no mass controls are found, display a message
    print '<div class="table-row">';
    print '<div class="table-cell" colspan="6">' . $langs->trans('NoSubControlFound') . '</div>';
    print '</div>';
}

$object->fetch($mainControlId);

?>
