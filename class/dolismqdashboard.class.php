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
        $getNbControlsByVerdict = self::getNbControlsByVerdict();
        $getNbControlsByVerdict2 = self::getNbControlsTagsByVerdict();

        $array['control']['graphs'] = [$getNbControlsByVerdict2, $getNbControlsByVerdict];

        return $array;
    }

    /**
     * Get controls by verdict.
     *
     * @return array      Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getNbControlsByVerdict(): array
    {
        global $db, $langs;

        require_once __DIR__ . '/control.class.php';

        $control = new Control($db);

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NbControlByVerdict');
        $array['picto'] = 'tasks';

        // Graph parameters
        $array['width']   = 800;
        $array['height']  = 400;
        $array['type']    = 'pie';
        $array['dataset'] = 1;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('Test'),
                'color' => '#999999'
            ],
            1 => [
                'label' => $langs->transnoentities('OK'),
                'color' => '#47e58e'
            ],
            2 => [
                'label' => $langs->transnoentities('KO'),
                'color' => '#e05353'
            ],
        ];

        $arrayNbControlByVerdict = [];
        $controls = $control->fetchAll('', '', 0, 0, ['customsql' => 'status >= 1']);
        if (is_array($controls) && !empty($controls)) {
            foreach ($controls as $control) {
                if (empty($control->verdict)) {
                    $arrayNbControlByVerdict[0]++;
                } else {
                    $arrayNbControlByVerdict[$control->verdict]++;
                }
            }
        }

        $array['data'] = $arrayNbControlByVerdict;

        return $array;
    }

    /**
     * Get controls by verdict.
     *
     * @return array      Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getNbControlsTagsByVerdict(): array
    {
        global $db, $langs;

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        require_once __DIR__ . '/control.class.php';

        $control    = new Control($db);
        $category = new Categorie($db);

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NbControlByVerdict');
        $array['picto'] = 'tasks';

        // Graph parameters
        $array['width']   = 800;
        $array['height']  = 400;
        $array['type']    = 'bar';
        $array['dataset'] = 3;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('Test'),
                'color' => '#999999'
            ],
            1 => [
                'label' => $langs->transnoentities('OK'),
                'color' => '#47e58e'
            ],
            2 => [
                'label' => $langs->transnoentities('KO'),
                'color' => '#e05353'
            ]
        ];

        $categories = $category->get_all_categories('control');
        if (is_array($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $arrayNbControlByVerdict = [];
                $controls = $category->getObjectsInCateg('control');
                if (is_array($controls) && !empty($controls)) {
                    foreach ($controls as $control) {
                        if (empty($control->verdict)) {
                            $arrayNbControlByVerdict[0]++;
                        } else {
                            $arrayNbControlByVerdict[$control->verdict]++;
                        }
                    }
                    $array['data'][] = [$category->label, $arrayNbControlByVerdict[0],  $arrayNbControlByVerdict[1], $arrayNbControlByVerdict[2]];
                }
            }
        }

        return $array;
    }
}
