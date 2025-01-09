<div class="flex" style="justify-content: space-between; align-items: center; border-radius: 0.7em; border-width: 2px; border-color: #F6F9FD; border-style: solid; padding: 10px; box-shadow: 0px 4px 2px 0px #D4E2F4">
    <div class="flex" style="gap: 10px;">
        <div>
            <div style="display: flex; justify-content: center; align-items: center;"><?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 100, 100, 0, 0, 1, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?></div>
        </div>
        <div class="flex flex-col justify-center">
            <?php if (isset($displayLastControl) && !empty($displayLastControl)) { ?>
                <div style="margin-bottom: 10px"><strong style="color: #4B77D3;"><?php print $langs->transnoentities('LastControl'); ?></strong></div>
            <?php } ?>
            <div><strong><i class="fas fa-tasks" style="color: #D55954;"></i> <?php print $object->ref ?></strong></div>
            <div><strong><i class="far fa-calendar opacitymedium"></i> <?php print dol_print_date($object->control_date, 'day'); ?></strong></div>
        </div>
    </div>
    <div class="flex" style="gap: 10px">
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
        print '<div class="wpeo-button button-' . $verdictObjectColor . '  button-square-60" style="line-height: inherit !important;"><div>' . $langs->transnoentities('VerdictObject') . '</div><i class="fas fa-' . $pictoObjectColor . ' fa-2x"></i></div><br>';
        print '<a href="' . dol_buildpath('/digiquali/view/control/control_card.php', 1) . '?id=' . $object->id . '" target="_blank" class="wpeo-button button-blue button-square-60" style="line-height: inherit !important;"><div>' . $langs->transnoentities('Watch') . '</div><i class="fas fa-eye fa-2x"></i></a><br>';
        ?>
    </div>
</div>
