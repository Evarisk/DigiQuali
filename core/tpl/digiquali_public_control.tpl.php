<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * Parameters :
 * Objects    : $answer, $object, $objectLine, $sheet
 * Variable   : $linkedObjectsData
 */ ?>

<?php require_once __DIR__ . '/../../lib/digiquali_control.lib.php'; ?>

<!--    <div>--><?php //print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 100, 100, 0, 0, 1, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1); ?><!--</div>-->
<?php
$out  = show_linked_object($objectLinked, $linkedObjectsData, $elementArray);
$out2 = show_control_object($objectLinked); ?>

<div class="">
    <div class="">
        <?php print $out['objectLinked']['title']; ?>
        <?php print $out['objectLinked']['name_field']; ?>
        <?php print $out['objectLinked']['qc_frequency']; ?>
        <?php print $out['parentLinkedObject']['title']; ?>
        <?php print $out['parentLinkedObject']['name_field']; ?>
    </div>
    <div class="">
        <?php print $out2['nextControl']['title']; ?>
        <?php print $out2['nextControl']['next_control_date']; ?>
        <?php print $out2['nextControl']['next_control']; ?>
    </div>
</div>
