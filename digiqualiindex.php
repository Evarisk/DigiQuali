<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    digiqualiindex.php
 * \ingroup digiquali
 * \brief   Home page of digiquali top menu
 */

// Load DigiQuali environment
if (file_exists('digiquali.main.inc.php')) {
    require_once __DIR__ . '/digiquali.main.inc.php';
} elseif (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

require_once __DIR__ . '/../saturne/core/tpl/index/index_view.tpl.php';
