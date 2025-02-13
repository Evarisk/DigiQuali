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

<div class="public-control-documentation">
    <?php foreach ($linkedObjectInfoArray['fileArray'] as $fileName) { ?>
        <div class="card has-margin">
            <div class="card-thumbnail size-min" style="background: #3E41FF;">
                <i class="card-thumbnail-icon fas fa-file"></i>
            </div>
            <div class="card-container">
                <div class="information-label size-l"><?php echo $fileName['name']; ?></div>
            </div>
            <div class="card-actions">
                <a class="wpeo-button button-square-40 button-rounded" href="<?php echo DOL_URL_ROOT . '/document.php?modulepart=' . 'product&entity=' . $conf->entity . '&file=' . urlencode($fileName['level1name'] . '/' .  $fileName['name']); ?>" target="_blank">
                    <i class="button-icon fa fa-download"></i>
                </a>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($links)) {
        foreach ($links as $link) { ?>
            <div class="card has-margin">
                <div class="card-thumbnail size-min" style="background: #0EECAD;">
                    <i class="card-thumbnail-icon fas fa-link"></i>
                </div>
                <div class="card-container">
                    <div class="information-label size-l"><?php echo $langs->transnoentities($link->label); ?></div>
                </div>
                <div class="card-actions">
                    <a class="wpeo-button button-square-40 button-rounded" href="<?php echo $link->url ?>" target="_blank">
                        <i class="button-icon fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        <?php }
    } ?>
</div>
