<div class="flex" style="gap: 10px;">
    <div style="display: flex; justify-content: center; align-items: center;"><?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 100, 100, 0, 0, 1, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>

    <div>
        <div>
            <div style="margin-bottom: 10px"><strong style="color: #4B77D3;"><?php print $langs->transnoentities($linkedObjectsData['langs']); ?></strong></div>
            <?php
                foreach ($object->linkedObjects as $linkableType => $linkableObject) {
                    foreach ($linkableObject as $objectItem) {
                        if ($linkableType == 'productbatch') {
                            ?>
                            <div><strong><?php print img_picto('', $elementArray['productlot']['picto'], 'class="pictofixedwidth"') . $objectItem->batch; ?></strong></div>
                            <?php
                            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                            $product = new Product($db);
                            $product->fetch($objectItem->fk_product);
                            ?>
                            <div><strong><?php print img_picto('', $elementArray['product']['picto'], 'class="pictofixedwidth"') . $product->ref; ?></strong></div>
                            <?php
                        } else {
                            ?>
                            <div><strong><?php print img_picto('', $elementArray[$linkableType]['picto'], 'class="pictofixedwidth"') . $objectItem->ref; ?></strong></div>
                            <?php
                        }
                    }
                    if (!empty($object->next_control_date)) {
                        print '<hr><div style="font-size: 8px; font-weight: bold">' . $langs->trans('NextControl') . '<br>';
                        $nextControl          = floor(($object->next_control_date - dol_now('tzuser'))/(3600 * 24));
                        $nextControlDateColor = $object->getNextControlDateColor();
                        print dol_print_date($object->next_control_date, 'day') . '<br>' . $langs->trans('Remain') . '<br>';
                        print '</div>';
                        print '<div class="wpeo-button" style="padding: 0; font-size: 10px; background-color: ' . $nextControlDateColor .'; border-color: ' . $nextControlDateColor .'">' . $nextControl . ' ' . $langs->trans('Days') . '</div>';
                      } ?>
                </td></tr>
            </table>
        </div>
        <div>
            <div><strong><i class="fa fa-history opacitymedium"></i> <?php print $langs->transnoentities('ControlPeriodicity') . ' : ' . $objectLinked->qc_frequency . ' ' . $langs->transnoentities('days') ; ?></strong></div>
            <div><strong><i class="far fa-calendar opacitymedium"></i> <?php print $langs->transnoentities('LastControl') . ' : ' . dol_print_date($object->control_date, 'day') ; ?></strong></div>
            <div><strong><i class="far fa-calendar opacitymedium"></i> <?php print $langs->transnoentities('NextControl') . ' : ' . dol_print_date(dol_time_plus_duree($object->control_date, $objectLinked->qc_frequency, 'd'), 'day') ; ?></strong></div>
            <div><i class="far fa-calendar" style="opacity: 0;"></i> <span style="color: #47e58e;"><?php print $langs->transnoentities('In') . ' ' . num_between_day(dol_now(), dol_time_plus_duree($object->control_date, $objectLinked->qc_frequency, 'd')) . ' ' . $langs->transnoentities('days'); ?></span></div>
        </div>
    </div>
</div>
