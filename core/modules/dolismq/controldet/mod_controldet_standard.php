<?php
/* Copyright (C) 2022 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file    core/modules/dolismq/controldet/mod_controldet_standard.php
 *  \ingroup dolismq
 *  \brief   File of class to manage controldet numbering rules standard.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to manage controldet numbering rules standard.
 */
class mod_controldet_standard extends ModeleNumRefSaturne
{
    /**
     * @var string Numbering module ref prefix.
     */
    public string $prefix = 'FCR';

    /**
     * @var string Name.
     */
    public string $name = 'Anthée';

    /**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'mod_controldet_standard';

    /**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;
}