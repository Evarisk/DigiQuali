<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \file    core/boxes/dolismqwidget1.php
 * \ingroup dolismq
 * \brief   Widget provided by DoliSMQ
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
		global $langs;

		// Use configuration value for max lines count
		$this->max = $max;

		require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";

		require_once __DIR__ . '/../../class/control.class.php';
		require_once __DIR__ . '/../../class/sheet.class.php';

		$categorystatic = new Categorie($this->db);
		$sheet          = new Sheet($this->db);
		$controlstatic  = new Control($this->db);
		$form           = new Form($this->db);

		$cookie_name     = 'DOLUSERCOOKIE_boxfilter_control';
		$boxcontent      = '';
		$filterValue     = 'all';
		$fromid          = GETPOST('fromid', 'int'); // element type
		$fromtype        = GETPOST('fromtype', 'alpha'); // element type

		//$textHead = $langs->trans($category->label, $max);

		$boxHeads = array();
		$boxContents = array();
		$controls = $controlstatic->fetchAll();

		if (in_array(GETPOST($cookie_name), array('all', 'last_control'))) {
			$filterValue = GETPOST($cookie_name);
		} elseif (!empty($_COOKIE[$cookie_name])) {
			$filterValue = preg_replace('/[^a-z_]/', '', $_COOKIE[$cookie_name]); // Clean cookie from evil data
		}

		if ($filterValue == 'last_control') {
			$textHead .= ' : ' . $langs->trans("WithLastControl");
		}

		// Populate the contents at runtime
		$boxcontent .= '<div id="ancor-idfilter' . $this->boxcode . '" style="display: block; position: absolute; margin-top: -100px"></div>' . "\n";
		$boxcontent .= '<div id="idfilter' . $this->boxcode . '" class="center" >' . "\n";
		$boxcontent .= '<form class="flat " method="POST" action="' . $_SERVER["PHP_SELF"] . '#ancor-idfilter' . $this->boxcode . '">' . "\n";
		$boxcontent .= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
		$selectArray = array('all' => $langs->trans("NoFilter"), 'last_control' => $langs->trans("WithLastControl"));
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

		if (!empty($controls)) {
			foreach ($controls as $control) {
				if (!empty($control->linkedObjectsIds)) {
					if (array_key_exists($fromtype, $control->linkedObjectsIds)) {
						$test = array_values($control->linkedObjectsIds[$fromtype]);
						if ($test[0] == $fromid) {
							$sheet->fetch($control->fk_sheet);
							$categories = $categorystatic->getListForItem($sheet->id, 'sheet');
							foreach ($categories as $category) {
								$boxHeads[$category['label']][] = array(
									// Title text
									'text' => $langs->trans($category['label']),
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
									'limit' => dol_strlen($langs->trans($category['label'])),
									// Adds translated " (Graph)" to a hidden form value's input (?)
									'graph' => false
								);

								$boxContents[$category['label']][$sheet->id][$control->id][0] = array(
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

								$boxContents[$category['label']][$sheet->id][$control->id][1] = array(
									0 => array(
										'td' => '',
										'text' => $control->getNomUrl(0) . ' - ' . $sheet->getNomUrl(0)
									),
									1 => array(
										'td' => 'class="right"',
										'text' => $control->getLibVerdict(3),
										'verdict' => $control->verdict
									)
								);
							}
						}
					}
				}
			}
		}

		$i = 0;
		foreach ($boxHeads as $boxHead) {
			$this->info_box_head[$i] = array_values($boxHead);
			$i++;
		}

		$i = 0;
		foreach ($boxContents as $boxContent) {
			$this->info_box_contents[$i] = array_values($boxContent);
			$i++;
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
		global $langs;

		foreach ($this->info_box_contents[$contents] as $sheet){
			if (count($sheet) > 0) {
				$contentArray[] = end($sheet);
			} else {
				foreach ($sheet as $control) {
					$contentArray[] = $control;
				}
			}
		}

		foreach ($contentArray as $content) {
			$boxContent[] = $content[1];
			if ($content[1][1]['verdict'] == 1) {
				$score++;
			}
		}

		$totalScore = ($score / count($boxContent)) * 100;

		$boxContent[] = array(
			0 => array(
				'td' => '',
				'text' => $langs->trans('Score')
			),
			1 => array(
				'td' => 'class="right"',
				'text' => price2num($totalScore, 'MT', 1) . ' %',
			)
		);

		return parent::showBox($this->info_box_head[$head][0], $boxContent, $nooutput);
	}
}
