<?php
/* Copyright (C) 2004-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2022 SuperAdmin <test@test.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dolismq/core/boxes/dolismqwidget1.php
 * \ingroup dolismq
 * \brief   Widget provided by DoliSMQ
 *
 * Put detailed description here.
 */

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";


/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class dolismqwidget1 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "dolismqbox";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "dolismq@dolismq";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('dolismq');

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();

	/**
	 * @var string 	Widget type ('graph' means the widget is a graph widget)
	 */
	public $widgettype = '';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $langs;
		// Translations
		$langs->loadLangs(array("boxes", "dolismq@dolismq"));

		parent::__construct($db, $param);

		$this->boxlabel = $langs->transnoentitiesnoconv("RegularyControl");

		$this->param = $param;

		$this->enabled = 1;         								 // Condition when module is enabled or not
		$this->hidden = ! ($user->rights->dolismq->control->read);   // Condition when module is visible by user (test on permission)
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $langs, $user;

		// Use configuration value for max lines count
		$this->max = $max;

		require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";

		require_once __DIR__ . '/../../class/control.class.php';
		require_once __DIR__ . '/../../class/sheet.class.php';

//		// Populate the contents at runtime
//		$this->info_box_contents = array(
//			0 => array( // First line
//				0 => array( // First Column
//					//  HTML properties of the TR element. Only available on the first column.
//					'tr' => 'class="left"',
//					// HTML properties of the TD element
//					'td' => '',
//
//					// Main text for content of cell
//					'text' => 'First cell of first line',
//					// Link on 'text' and 'logo' elements
//					'url' => 'http://example.com',
//					// Link's target HTML property
//					'target' => '_blank',
//					// Fist line logo (deprecated. Include instead logo html code into text or text2, and set asis property to true to avoid HTML cleaning)
//					//'logo' => 'monmodule@monmodule',
//					// Unformatted text, added after text. Usefull to add/load javascript code
//					'textnoformat' => '',
//
//					// Main text for content of cell (other method)
//					//'text2' => '<p><strong>Another text</strong></p>',
//
//					// Truncates 'text' element to the specified character length, 0 = disabled
//					'maxlength' => 0,
//					// Prevents HTML cleaning (and truncation)
//					'asis' => false,
//					// Same for 'text2'
//					'asis2' => true
//				),
//				1 => array( // Another column
//					// No TR for n≠0
//					'td' => '',
//					'text' => 'Second cell',
//				)
//			),
//			1 => array( // Another line
//				0 => array( // TR
//					'tr' => 'class="left"',
//					'text' => 'Another line'
//				),
//				1 => array( // TR
//					'tr' => 'class="left"',
//					'text' => 'sdsdsdsdsds'
//				)
//			),
//			2 => array( // Another line
//				0 => array( // TR
//					'tr' => 'class="left"',
//					'text' => 'sdss'
//				),
//				1 => array( // TR
//					'tr' => 'class="left"',
//					'text' => 'dsdsdsdsd'
//				)
//			),
//		);

		$categorystatic = new Categorie($this->db);
		$sheet    = new Sheet($this->db);
		$control  = new Control($this->db);

		$form     = new Form($this->db);

		$cookie_name     = 'DOLUSERCOOKIE_boxfilter_control';
		$boxcontent      = '';
		$filterValue     = 'all';
		$regulatoryScore = 0;
		$fromtype        = GETPOST('fromtype', 'alpha'); // element type

		$allCategories = $categorystatic->get_all_categories('sheet');
		if (!empty($allCategories)) {
			foreach ($allCategories as $category) {
				$categorystatic->fetch_optionals();
				$categorystatic->fetch(0, $langs->transnoentities($category->label), 'sheet');
				$sheets = $categorystatic->getObjectsInCateg('sheet', 0);

				if (!empty($sheets)) {
					foreach ($sheets as $sheet) {
						$controls = $control->fetchAll('', '', 0, 0, array('customsql' => 'fk_sheet = ' . $sheet->id));
//						echo '<pre>'; print_r( $category->label ); echo '</pre>';
//						echo '<pre>'; print_r( $controls ); echo '</pre>';
					}

					$elementLinked = json_decode($sheet->element_linked);

					if (!empty($controls) && $elementLinked->$fromtype == 1) {

						$score = 0;
						$totalScore = 0;

						$textHead = $langs->trans($category->label, $max);

						if (in_array(GETPOST($cookie_name), array('all', 'last_control'))) {
							$filterValue = GETPOST($cookie_name);
						} elseif (!empty($_COOKIE[$cookie_name])) {
							$filterValue = preg_replace('/[^a-z_]/', '', $_COOKIE[$cookie_name]); // Clean cookie from evil data
						}

						if ($filterValue == 'last_control') {
							$textHead .= ' : ' . $langs->trans("WhichLastControl");
						}

						// Populate the head at runtime
						$this->info_box_head[$category->id] = array(
							// Title text
							'text' => $textHead,
							// Add a link
							'sublink' => '',
							// Sublink icon placed after the text
							'subpicto' => 'filter.png',
							// Sublink icon HTML alt text
							'subtext' => '',
							// Sublink HTML target
							'target' => 'none', // Set '' to get target="_blank"
							// HTML class attached to the picto and link
							'subclass' => 'linkobject boxfilter',
							// Limit and truncate with "…" the displayed text lenght, 0 = disabled
							'limit' => dol_strlen($textHead),
							// Adds translated " (Graph)" to a hidden form value's input (?)
							'graph' => false
						);

						// Populate the contents at runtime
						$boxcontent .= '<div id="ancor-idfilter' . $this->boxcode . '" style="display: block; position: absolute; margin-top: -100px"></div>' . "\n";
						$boxcontent .= '<div id="idfilter' . $this->boxcode . '" class="center" >' . "\n";
						$boxcontent .= '<form class="flat " method="POST" action="' . $_SERVER["PHP_SELF"] . '#ancor-idfilter' . $this->boxcode . '">' . "\n";
						$boxcontent .= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
						$selectArray = array('all' => $langs->trans("NoFilter"), 'last_control' => $langs->trans("WhichLastControl"));
						$boxcontent .= $form->selectArray($cookie_name, $selectArray, $filterValue);
						$boxcontent .= '<button type="submit" class="button buttongen button-save">' . $langs->trans("Refresh") . '</button>';
						$boxcontent .= '</form>' . "\n";
						$boxcontent .= '</div>' . "\n";

						if (!empty($conf->use_javascript_ajax)) {
							$boxcontent .= '<script type="text/javascript">
										jQuery(document).ready(function() {
											jQuery("#idsubimg' . $this->boxcode . '").click(function() {
												jQuery(".showiffilter' . $this->boxcode . '").toggle();
											});
										});
										</script>';
							// set cookie by js
							$boxcontent .= '<script>date = new Date(); date.setTime(date.getTime()+(30*86400000)); document.cookie = "' . $cookie_name . '=' . $filterValue . '; expires= " + date.toGMTString() + "; path=/ "; </script>';
						}

						$this->info_box_contents[$category->id][0] = array(
							0 => array(
								'tr' => 'class="nohover showiffilter' . $this->boxcode . ' hideobject"',
								'td' => 'class="nohover"',
								'textnoformat' => $boxcontent,
							),
							1 => array(
								'td' => 'class="right"',
								'text' => '',
							)
						);

						$i = 1;
						while ($i <= count($controls)) {
							foreach ($controls as $control) {
								$this->info_box_contents[$category->id][$i] = array(
									0 => array(
										'td' => '',
										'text' => $control->getNomUrl(0) . ' - ' . $sheet->getNomUrl(0)
									),
									1 => array(
										'td' => 'class="right"',
										'text' => $control->getLibVerdict(3)
									)
								);

								if ($control->verdict == 1) {
									$score++;
								}

								$totalScore = ($score / count($controls)) * 100;

								$this->info_box_contents[$category->id][$i+1] = array(
									0 => array(
										'td' => '',
										'text' => $langs->trans('Score')
									),
									1 => array(
										'td' => 'class="right"',
										'text' => price2num($totalScore, 'MT', 1) . ' %',
									)
								);
								$i++;
							}
						}
					}
				}
			}
			//		$this->info_box_contents[$key+3] = array(
			//			0 => array(
			//				'td' => '',
			//				'text' => $langs->trans('RegulatoryThreshold')
			//			),
			//			1 => array(
			//				'td' => 'class="right"',
			//				'text' => price2num($category->array_options['options_seuil'], '', 2) . ' %'
			//			)
			//		);
		}
	}

	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head       Array with properties of box title
	 * @param array $contents   Array with properties of box lines
	 * @param int   $nooutput   No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head[$head], $this->info_box_contents[$contents], $nooutput);
	}
}
