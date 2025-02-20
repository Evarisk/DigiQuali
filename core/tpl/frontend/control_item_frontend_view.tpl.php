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
 * \file    core/tpl/frontend/control_item_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for control item public view
 */

/**
 * The following vars must be defined:
 * Variable : $linkedObject
 */

$controlInfoArray = get_control_infos($linkedObject); ?>

<?php foreach ($controlInfoArray['control'] as $controlInfo) : ?>
    <div class="card has-margin">
        <div class="card-thumbnail"><?php echo $controlInfo['image']; ?></div>
        <div class="card-container">
            <div class="information-type"><?php echo $controlInfo['title']; ?></div>
            <div class="information-label size-l"><?php echo $controlInfo['ref']; ?></div>
            <div class="information-label"><?php echo $controlInfo['control_date']; ?></div>
            <div class="information-type"><?php echo $controlInfo['sheet_title']; ?></div>
            <div class="information-label"><?php echo $controlInfo['sheet_ref']; ?></div>

        </div>
        <div class="card-actions">
            <div class="information-label"><?php echo $controlInfo['view_button']; ?></div>
            <div class="information-label"><?php echo $controlInfo['verdict']; ?></div>
        </div>
    </div>
<?php endforeach; ?>
