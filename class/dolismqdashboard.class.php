<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/dolismqdashboard.class.php
 * \ingroup dolismq
 * \brief   Class file for manage DolismqDashboard.
 */

/**
 * Class for DolismqDashboard.
 */
class DolismqDashboard
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Load dashboard info.
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        require_once __DIR__ . '/control.class.php';

        $control = new Control($this->db);

        $array['control'] = $control->load_dashboard();

        return $array;
    }
}
