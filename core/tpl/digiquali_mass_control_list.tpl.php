<?php

/**
 * \file    digiquali_mass_control_list.tpl.php
 * \ingroup digiquali
 * \brief   Template for displaying the list of mass controls linked to an object
 */

// Fetch the list of mass controls linked to the object
$massControlList = $object->fetchAll('', '', 0, 0, ['fk_control' => $object->id]);
print '</form>';
// Start the responsive table container
print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';

// Load and print the title for the control list section
print load_fiche_titre($langs->trans('LinkedControlList'), '', '');

// Start the table
print '<div class="wpeo-table table-flex table-3">';

// Define table headers with appropriate translations
$tableHeaders = [
    $langs->trans('Nom'),
    $langs->trans('Verdict'),
    $langs->trans('NoteControl'),
    $langs->trans('Answers'),
    $langs->trans('QRCode'),
    $langs->trans('Document'),
    $langs->trans('Action'),
];

// Create header row using divs
print '<div class="table-row header-row">';
foreach ($tableHeaders as $header) {
    print '<div class="table-cell header-cell center">' . $header . '</div>';
}
print '</div>';

$mainControlId = $object->id;
// Check if there are any mass controls and print them
if (is_array($massControlList) && !empty($massControlList)) {
    foreach ($massControlList as $massControl) {
        $object = $massControl;
        $massControl->fetch_optionals();
        $massControl->fetchLines();
        // Fetch the public note if it exists

        print '<div class="table-row sub-control-'. $massControl->id .'">';
        print '<div class="table-cell center">' . $massControl->getNomUrl(1) . '</div>';

        // Verdict section with interactive OK/KO buttons
        print '<div class="table-cell center">';
        print '<div class="verdict-container">';
        print '<label class="verdict-option">';
        print '<input type="radio" name="verdict' . $massControl->id . '" value="1" ' . ($massControl->verdict == '1' ? 'checked' : '') . '>';
        print '<span class="verdict-box verdict-ok">OK</span>';
        print '</label>';
        print '<label class="verdict-option">';
        print '<input type="radio" name="verdict' . $massControl->id . '" value="0" ' . ($massControl->verdict == '0' ? 'checked' : '') . '>';
        print '<span class="verdict-box verdict-ko">KO</span>';
        print '</label>';
        print '</div>';
        print '</div>';

        // Note Control section displaying the public note
        print '<div class="table-cell center"><textarea type="text" class="note-public">' . $massControl->note_public . '</textarea></div>';

        print '<div class="table-cell center">';
        $questionCounter = 0;
        if (!empty($questionIds)) {
            $questionCounter = count($questionIds);
        }

        $answerCounter = 0;
        if (is_array($massControl->lines) && !empty($massControl->lines)) {
            foreach ($massControl->lines as $massControlLine) {
                if (dol_strlen($massControlLine->answer) > 0) {
                    $answerCounter++;
                }
            }
        }
        //affiche le nombre de questions répondues
        print '<span class="answerCounter">' . $answerCounter . '/' . $questionCounter . '</span>';
        print '<button class="butAction answerSubControl modal-open" data-control-id="'. $massControl->id .'">';
        print $langs->trans('Answers');
        print '<input type="hidden" class="modal-options" data-modal-to-open="modalSubControl'. $massControl->id .'">';
        print '</button>';
        print '</div>';

        // Additional cells for QRCode, Document, and Action can be filled in as needed
        print '<div class="table-cell center">'. saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $massControl->ref . '/qrcode/', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 0, 'control/' . $massControl->ref . '/qrcode/', $massControl, '', 0, 0) . '</div>';
        print '<div class="table-cell center">';
        print '</div>';
        print '<div class="table-cell center">';
        if ($massControl->verdict > 0 && $answerCounter == $questionCounter) {
            $displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
            print '<span class="lockSubControl butAction" id="actionButtonLockSubControl" data-control-id="'. $massControl->id .'" data-mass-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
        } else {
            $displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Save');
            print '<span class="saveSubControl butAction" id="actionButtonSaveSubControl" data-control-id="'. $massControl->id .'" data-mass-control-id="'. $mainControlId .'">' . $displayButton . '</span>';
        }

        print '</div>';

        print '<div class="wpeo-modal" id="modalSubControl'. $massControl->id .'">';
        print '<div class="modal-container">';
        print '<div class="modal-content">';
        print load_fiche_titre($langs->trans('LinkedQuestionsList') . ' - ' . $massControl->getNomUrl(1), '', '');
        $conf->global->DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION = 0;
        print '<div id="tablelines" class="question-answer-container noborder noshadow">';
        require __DIR__ . '/../../core/tpl/digiquali_answers.tpl.php';
        print '</div>';
        print '</div>';
        print '</div>';
        print '</div>';
        print '</div>';


    }
} else {
    // If no mass controls are found, display a message
    print '<div class="table-row">';
    print '<div class="table-cell" colspan="6">' . $langs->trans('NoMassControlFound') . '</div>';
    print '</div>';
}
$object->fetch($id);
print '</div>'; // End of table
print '</div>'; // End of responsive container
?>
