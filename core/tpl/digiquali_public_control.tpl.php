<div class="signature-container" style="max-width: 1000px;">
    <div class="wpeo-gridlayout grid-2">
        <div style="display: flex; justify-content: center; align-items: center;"><?php echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
        <div class="informations">
            <?php foreach ($elementArray as $linkableObjectType => $linkableObject) {
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
                        } ?>

                        <div style="margin-bottom: 10px"><strong><?php echo img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"') . $langs->transnoentities($linkableObject['langs']); ?></strong></div>
                        <div class="wpeo-table table-flex">
                            <div class="table-row">
                                <div class="table-cell table-250">
                                    <?php echo img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"') . $objectName . '<br><i class="far fa-check-circle pictofixedwidth"></i>' . $langs->trans('VerdictObject'); ?>
                                        <?php if ($linkedObject->array_options['options_qc_frequency'] > 0 && getDolGlobalInt('SHOW_QC_FREQUENCY_PUBLIC_INTERFACE')) {
                                            print '<br>' . $langs->transnoentities('QcFrequency') . ' : ' . $linkedObject->array_options['options_qc_frequency'];
                                        } ?>
                                </div>
                                <div class="table-cell table-end">
                                    <?php if ($object->status == $object::STATUS_DRAFT) {
                                        $verdictObjectColor = 'primary';
                                        $pictoObjectColor   = 'hourglass-start';
                                    } elseif ($object->status == $object::STATUS_VALIDATED) {
                                        $verdictObjectColor = 'primary';
                                        $pictoObjectColor   = 'hourglass-half';
                                    } elseif (!empty($object->next_control_date) && $object->next_control_date - dol_now() < 0) {
                                        $verdictObjectColor = 'red';
                                        $pictoObjectColor   = 'exclamation';
                                    } elseif ($object->verdict > 1) {
                                        $verdictObjectColor = 'red';
                                        $pictoObjectColor   = 'exclamation';
                                    } else {
                                        $verdictObjectColor = 'green';
                                        $pictoObjectColor   = 'check';
                                    }
                                    print '<div class="wpeo-button button-' . $verdictObjectColor . '  button-square-60"><i class="fas fa-2x fa-' . $pictoObjectColor . ' button-icon"></i></div><br>'; ?>
                                </div>
                            </div>
                        </div>
                <?php }
                }
            } ?>
            <br><div style="margin-bottom: 10px"><strong><?php echo $sheet->getNomUrl(1, 'nolink', 1); ?></strong></div>
            <div class="wpeo-table table-flex">
                <div class="table-row">
                    <div class="table-cell table-200">
                        <?php echo $object->getNomUrl(1, 'nolink') . '<br>';
                        echo '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict') . '<br>';
                        echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('ControlDate'); ?>
                    </div>
                    <div class="table-cell table-end">
                        <?php $verdictColor = $object->verdict == 1 ? 'green' : ($object->verdict == 2 ? 'red' : 'grey');
                        if ($object->status < $object::STATUS_LOCKED) {
                            print $object->getLibStatut(5);
                            print '<br>';
                            print '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('NonFinalVerdict');
                        } else {
                            print '<div class="wpeo-button button-'. $verdictColor .'">' . $object->fields['verdict']['arrayofkeyval'][(!empty($object->verdict)) ? $object->verdict : 3] . '</div>';
                        }
                        print '<br>';
                        echo dol_print_date($object->control_date, 'day'); ?>
                    </div>
                </div>
                <?php if (!empty($object->next_control_date)) : ?>
                    <div class="table-row" style="background: rgba(0,0,0,.05) !important;">
                        <div class="table-cell table-300">
                            <?php echo img_picto('', $object->picto, 'class="pictofixedwidth"') . $langs->trans('NextControl') . '<br>';
                            echo img_picto('', 'calendar', 'class="pictofixedwidth"') . $langs->trans('NextControlDate'); ?>
                        </div>
                        <div class="table-cell table-100 table-end">
                            <?php echo '<div class="badge badge-status ' . ((($object->next_control_date - dol_now()) > 0) ? 'badge-status4' : 'badge-status8') . '">' . floor(($object->next_control_date - dol_now())/(3600 * 24)) . ' ' . $langs->trans('Days') . '</div><br>';
                            echo dol_print_date($object->next_control_date, 'day'); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

