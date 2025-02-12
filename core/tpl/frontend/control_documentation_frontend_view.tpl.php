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
* \file    core/tpl/frontend/control_documentation_frontend_view.tpl.php
* \ingroup digiquali
* \brief   Template page for control documentation public view
*/

/**
* The following vars must be defined:
* Variable : $linkedObject
*/

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements);

$links = [];
$link->fetchAll($links, $linkedObject->element, $linkedObject->id); ?>

<div class="flex flex-col" style="gap: 10px;">
    <?php if (!empty($links)) {
        foreach ($links as $link) { ?>
            <a href="<?php echo $link->url ?>" target="_blank">
                <div class="wpeo-button">
                    <i class="fas fa-external-link-alt pictofixedwidth"></i><?php echo $langs->transnoentities($link->label); ?>
                </div>
            </a>
        <?php }
    } ?>
</div>

<table class="noborder centpercent">
    <tr class="liste_titre">
        <td><?php echo $langs->transnoentities('Name'); ?></td>
        <td class="center nowraponall"><?php echo $langs->transnoentities('Date'); ?></td>
        <td class="center"><?php echo $langs->transnoentities('Upload'); ?></td>
    </tr>
    <?php foreach ($linkedObjectInfoArray['fileArray'] as $fileName) { ?>
        <tr>
            <td><?php echo $fileName['name']; ?></td>
            <td class="center nowraponall"><?php echo dol_print_date($fileName['date'], 'dayhour', 'tzuser'); ?></td>
            <td class="center">
                <a href="<?php echo DOL_URL_ROOT . '/document.php?modulepart=' . 'product&entity=' . $conf->entity . '&file=' . urlencode($fileName['level1name'] . '/' .  $fileName['name']); ?>" target="_blank">
                    <i class="fa fa-download"></i>
                </a>
            </td>
        </tr>
    <?php } ?>
</table>
