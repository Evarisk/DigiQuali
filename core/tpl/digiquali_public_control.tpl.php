<div class="signature-container" style="max-width: 1000px;">
    <div class="wpeo-gridlayout grid-2">
        <div style="display: flex; justify-content: center; align-items: center;"><?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
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

                        <div style="margin-bottom: 10px"><strong><?php print img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"') . $langs->transnoentities($linkableObject['langs']); ?></strong></div>
                        <table class="centpercent" style="background: rgba(0,0,0,.05); padding: 10px;">
                            <tr><td class="tdoverflowmax200" style="min-width: 125px;">
                                <?php print img_picto('', $linkableObject['picto'], 'class="pictofixedwidth"') . $objectName . '<br><i class="far fa-check-circle pictofixedwidth"></i>' . $langs->trans('VerdictObject'); ?>
                                <?php if ($linkedObject->array_options['options_qc_frequency'] > 0 && getDolGlobalInt('DIGIQUALI_SHOW_QC_FREQUENCY_PUBLIC_INTERFACE')) {
                                    print '<br>' . $langs->transnoentities('QcFrequency') . ' : ' . $linkedObject->array_options['options_qc_frequency'];
                                } ?>
                            </td>
                            <td class="tdoverflowmax200 center">
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
                            </td></tr>
                        </table>
                <?php }
                }
            } ?>
            <br><div style="margin-bottom: 10px"><strong><?php print $object->getNomUrl(1); ?></strong></div>
            <table class="centpercent" style="background: rgba(0,0,0,.05); padding: 10px;">
                <tr><td class="tdoverflowmax200">
                    <?php print $langs->trans('BasedOnModel') . ' : <br>' . $sheet->getNomUrl(1, '', 0, '', -1, 1) . '<br>';
                    print '<i class="far fa-check-circle"></i> ' . $langs->trans('Verdict') . '<br>';
                    print '<div style="margin-top: 10px">' . saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $object->ref . '/qrcode/', 'small', 1, 0, 0, 0, 70, 70, 0, 0, 1, 'control/'. $object->ref . '/qrcode/', $object, '', 0, 0) . '</div>'; ?>
                </td>
                <td class="tdoverflowmax200 center">
                    <?php $verdictColor = $object->verdict == 1 ? 'green' : ($object->verdict == 2 ? 'red' : 'grey');
                    if ($object->status < $object::STATUS_LOCKED) {
                        print $object->getLibStatut(5);
                        print '<br>';
                        print '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('NonFinalVerdict');
                    } else {
                        print '<div class="wpeo-button button-' . $verdictColor . ' button-square-60">' . $object->fields['verdict']['arrayofkeyval'][(!empty($object->verdict)) ? $object->verdict : 3] . '</div>';
                    }
                    if (!empty($object->next_control_date)) {
                        print '<hr><div style="font-size: 8px; font-weight: bold">' . $langs->trans('NextControl') . '<br>';
                        $nextControl = floor(($object->next_control_date - dol_now())/(3600 * 24));
                        $nextControlColor = $nextControl < 0 ? getDolGlobalString('DIGIQUALI_PASSED_TIME_CONTROL_COLOR') : ($nextControl <= 30 ? getDolGlobalString('DIGIQUALI_URGENT_TIME_CONTROL_COLOR') : ($nextControl <= 60 ? getDolGlobalString('DIGIQUALI_MEDIUM_TIME_CONTROL_COLOR') : getDolGlobalString('DIGIQUALI_PERFECT_TIME_CONTROL_COLOR')));
                        print dol_print_date($object->next_control_date, 'day') . '<br>' . $langs->trans('Remain') . '<br>';
                        print '</div>';
                        print '<div class="wpeo-button" style="padding: 0; font-size: 10px; background-color: ' . $nextControlColor .'; border-color: ' . $nextControlColor .'">' . $nextControl . ' ' . $langs->trans('Days') . '</div>';
                      } ?>
                </td></tr>
            </table>
        </div>
    </div>
</div>

