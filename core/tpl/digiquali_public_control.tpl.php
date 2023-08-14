<div class="signature-container" style="max-width: 1000px;">
    <div class="wpeo-gridlayout grid-2">
        <div style="display: flex; justify-content: center; align-items: center;"><?php echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
        <div class="informations">
            <div style="margin-bottom: 10px"><strong><?php echo $object->getNomUrl(1, 'nolink'); ?></strong></div>
            <div class="wpeo-table table-flex">
                <div class="table-row">
                    <div class="table-cell"><?php echo '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict'); ?></div>
                    <?php
                    $verdictColor = $object->verdict == 1 ? 'green' : ($object->verdict == 2 ? 'red' : 'grey');
                    print '<div class="table-cell table-end">';
                    if ($object->status < $object::STATUS_LOCKED) {
                        print $object->getLibStatut(5);
                        print '<br>';
                        print '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('NonFinalVerdict');
                    } else {
                        print '<div class="wpeo-button button-'. $verdictColor .'">' . $object->fields['verdict']['arrayofkeyval'][(!empty($object->verdict)) ?: 3] . '</div>';
                    }
                    print '</div>';
                    ?>
                </div>
                <div class="table-row">
                    <div class="wpeo-table table-cell table-full">
                        <?php
                        foreach ($elementArray as $linkableObjectType => $linkableObject) {
                            if ($linkableObject['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableObject['link_name']]))) {

                                $className    = $linkableObject['className'];
                                $linkedObject = new $className($db);

                                $linkedObjectKey = array_key_first($object->linkedObjectsIds[$linkableObject['link_name']]);
                                $linkedObjectId  = $object->linkedObjectsIds[$linkableObject['link_name']][$linkedObjectKey];


                                $result = $linkedObject->fetch($linkedObjectId);
                                if ($result > 0) {
                                    $linkedObject->fetch_optionals();

                                    $objectName = '';
                                    $objectNameField = $linkableObject['name_field'];

                                    if (strstr($objectNameField, ',')) {
                                        $nameFields = explode(', ', $objectNameField);
                                        if (is_array($nameFields) && !empty($nameFields)) {
                                            foreach ($nameFields as $subnameField) {
                                                $objectName .= $linkedObject->$subnameField . ' ';
                                            }
                                        }
                                    } else {
                                        $objectName = $linkedObject->$objectNameField;
                                    }

                                    print '<div class="table-row">';
                                    print '<div class="table-cell table-150">';
                                    print '<strong>';
                                    print $langs->transnoentities($linkableObject['langs']);
                                    print '</div>';
                                    print '<div class="table-cell table-end">';
                                    print $objectName . ' ' . img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"');

                                    if ($linkedObject->array_options['options_qc_frequency'] > 0) {
                                        $objectQcFrequency = $linkedObject->array_options['options_qc_frequency'];
                                        print '<br>';
                                        print $langs->transnoentities('QcFrequency') . ' : ' . $objectQcFrequency;
                                    }
                                    print '</strong>';
                                    print '</div>';
                                    print '</div>';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="table-row">
                    <div class="table-cell table-200"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('ControlDate'); ?></div>
                    <div class="table-cell table-end"><?php echo dol_print_date($object->control_date, 'day'); ?></div>
                </div>
                <?php if (!empty($object->next_control_date)) : ?>
                    <div class="table-row">
                        <div class="table-cell table-300"><?php echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('NextControlDate'); ?></div>
                        <div class="table-cell table-end"><?php echo dol_print_date($object->next_control_date, 'day'); ?></div>
                    </div>
                    <div class="table-row">
                        <div class="table-cell table-200"><?php echo img_picto('', $object->picto, 'class="pictofixedwidth"') . $langs->trans('NextControl'); ?></div>
                        <div class="table-cell table-75 table-end badge badge-status <?php echo ((($object->next_control_date - dol_now()) > 0) ? 'badge-status4' : 'badge-status8'); ?>"><?php echo floor(($object->next_control_date - dol_now())/(3600 * 24)) . ' ' . $langs->trans('Days'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

