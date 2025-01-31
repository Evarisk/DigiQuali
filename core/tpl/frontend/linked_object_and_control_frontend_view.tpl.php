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
 * \file    core/tpl/linked_object_and_control_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for control public view
 */

/**
 * The following vars must be defined:
 * Variable : $linkedObject, $linkableElements
 */

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements);
$controlInfoArray      = get_control_infos($linkedObject); ?>

<div class="public-card__header wpeo-gridlayout grid-2">
    <div class="header-information">
        <div class="information-thumbnail"><?php print $linkedObjectInfoArray['linkedObject']['image']; ?></div>
        <div>
            <div class="information-type"><?php print $linkedObjectInfoArray['linkedObject']['title']; ?></div>
            <div class="information-label size-l"><?php print $linkedObjectInfoArray['linkedObject']['name_field']; ?></div>
            <div class="information-label objet-label"><?php print $linkedObjectInfoArray['linkedObject']['qc_frequency']; ?></div>
            <div class="information-type"><?php print $linkedObjectInfoArray['parentLinkedObject']['title']; ?></div>
            <div class="information-label"><?php print $linkedObjectInfoArray['parentLinkedObject']['name_field']; ?></div>
        </div>
    </div>
    <div class="header-objet">
        <div class="objet-container">
            <div class="objet-info">
                <div class="objet-type">
                    <?php print $controlInfoArray['nextControl']['title']; ?>
                </div>
                <div class="objet-label size-l">
                    <?php print $controlInfoArray['nextControl']['next_control_date']; ?>
                </div>
                <div class="objet-label" style="color: <?php print $controlInfoArray['nextControl']['next_control_date_color']; ?>;">
                    <?php print $controlInfoArray['nextControl']['next_control']; ?>
                </div>
            </div>
            <div class="objet-actions">
                <div class="wpeo-gridlayout grid-2 grid-gap-1">
                    <?php print $controlInfoArray['nextControl']['create_button']; ?>
                    <div class="wpeo-button button-square-60 button-radius-1 button-green">Statut OK</div>
                </div>
            </div>
        </div>
    </div>
</div>
