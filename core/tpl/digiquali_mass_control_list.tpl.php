<?php

/**
 * \file    digiquali_mass_control_list.tpl.php
 * \ingroup digiquali
 * \brief   Template for displaying the list of mass controls linked to an object
 */

// Fetch the list of mass controls linked to the object
$massControlList = $object->fetchAll('', '', 0, 0, ['fk_control' => $object->id]);

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
    $langs->trans('QRCode'),
    $langs->trans('Document'),
    $langs->trans('Action'),
];

// Create header row using divs
print '<div class="table-row header-row">';
foreach ($tableHeaders as $header) {
    print '<div class="table-cell header-cell">' . $header . '</div>';
}
print '</div>';

// Check if there are any mass controls and print them
if (is_array($massControlList) && !empty($massControlList)) {
    foreach ($massControlList as $massControl) {
        print '<div class="table-row">';
        print '<div class="table-cell">' . $massControl->getNomUrl(1) . '</div>';
        print '<div class="table-cell">' . $massControl->getVerdict() . '</div>';
        print '</div>';
    }
} else {
    // If no mass controls are found, display a message
    print '<div class="table-row">';
    print '<div class="table-cell" colspan="5">' . $langs->trans('NoMassControlFound') . '</div>';
    print '</div>';
}

print '</div>'; // End of table
print '</div>'; // End of responsive container

?>
