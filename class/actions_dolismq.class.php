<?php
/* Copyright (C) 2022 EVARISK <technique@evarisk.com>
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
 * \file    class/actions_dolismq.class.php
 * \ingroup dolismq
 * \brief   DoliSMQ hook overload.
 */

/**
 * Class ActionsDolismq
 */
class ActionsDolismq
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the constructCategory function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @return  int                             0 < on error, 0 on success, 1 to replace standard code
	 */
	public function constructCategory($parameters, &$object)
	{
		$error = 0; // Error counter

		if (($parameters['currentcontext'] == 'category')) {
			$tags = [
				'question' => [
					'id'        => 436301001,
					'code'      => 'question',
					'obj_class' => 'Question',
					'obj_table' => 'dolismq_question',
				],
				'sheet' => [
					'id'        => 436301002,
					'code'      => 'sheet',
					'obj_class' => 'Sheet',
					'obj_table' => 'dolismq_sheet',
				],
				'control' => [
					'id'        => 436301003,
					'code'      => 'control',
					'obj_class' => 'Control',
					'obj_table' => 'dolismq_control',
				]
			];
		}

		if (!$error) {
			$this->results = $tags;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param  array  $parameters Hook metadata (context, etc...)
	 * @param  object $object     The object to process
	 * @param  string $action     Current action (if set). Generally create or edit or null
	 * @return int                0 < on error, 0 on success, 1 to replace standard code
	 */
	public function doActions(array $parameters, $object, string $action): int
	{
		global $langs, $user;

		$error = 0; // Error counter

		if (preg_match('/categorycard/', $parameters['context'])) {
			$id = GETPOST('id');
			$elementId = GETPOST('element_id');
			$type = GETPOST('type');
			if ($id > 0 && $elementId > 0 && ($type == 'question' || $type == 'sheet' || $type == 'control') && $user->rights->dolismq->$type->write) {
				require_once __DIR__ . '/' . $type . '.class.php';
				$classname = ucfirst($type);
				$newobject = new $classname($this->db);

				$newobject->fetch($elementId);

				if (GETPOST('action') == 'addintocategory') {
					$result = $object->add_type($newobject, $type);
					if ($result >= 0) {
						setEventMessages($langs->trans("WasAddedSuccessfully", $newobject->ref), array());

					} else {
						if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							setEventMessages($langs->trans("ObjectAlreadyLinkedToCategory"), array(), 'warnings');
						} else {
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				} elseif (GETPOST('action') == 'delintocategory') {
					$result = $object->del_type($newobject, $type);
					if ($result < 0) {
						dol_print_error('', $object->error);
					}
					$action = '';
				}
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $conf, $form, $langs, $user;

		$error = 0; // Error counter

		if (preg_match('/categoryindex/', $parameters['context'])) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			print '<script src="../custom/dolismq/js/dolismq.js"></script>';
		} elseif (preg_match('/categorycard/', $parameters['context']) && preg_match('/viewcat.php/', $_SERVER["PHP_SELF"])) {
			$id = GETPOST('id');
			$type = GETPOST('type');

			// Load variable for pagination
			$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
			$sortfield = GETPOST('sortfield', 'aZ09comma');
			$sortorder = GETPOST('sortorder', 'aZ09comma');
			$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
			if (empty($page) || $page == -1) {
				$page = 0;
			}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
			$offset = $limit * $page;

			if ($type == 'question' || $type == 'sheet' || $type == 'control') {
				require_once __DIR__ . '/' . $type . '.class.php';

				$classname = ucfirst($type);
				$object = new $classname($this->db);

				$arrayObjects = $object->fetchAll();
				if (is_array($arrayObjects) && !empty($arrayObjects)) {
					foreach ($arrayObjects as $objectsingle) {
						$array[$objectsingle->id] = $objectsingle->ref;
					}
				}

				$category = new Categorie($this->db);
				$category->fetch($id);
				$objectsInCateg = $category->getObjectsInCateg($type, 0, $limit, $offset);

				if (!is_array($objectsInCateg) || empty($objectsInCateg)) {
					dol_print_error($this->db, $category->error, $category->errors);
				} else {
					// Form to add record into a category
					$out = '<br>';

					$out .= '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&type=' . $type . '">';
					$out .= '<input type="hidden" name="token" value="'.newToken().'">';
					$out .= '<input type="hidden" name="action" value="addintocategory">';

					$out .= '<table class="noborder centpercent">';
					$out .= '<tr class="liste_titre"><td>';
					$out .= $langs->trans("Add". ucfirst($type) . "IntoCategory") . ' ';
					$out .= $form->selectarray('element_id', $array, '', 1);
					$out .= '<input type="submit" class="button buttongen" value="'.$langs->trans("ClassifyInCategory").'"></td>';
					$out .= '</tr>';
					$out .= '</table>';
					$out .= '</form>';

					$out .= '<br>';

					//$param = '&limit=' . $limit . '&id=' . $id . '&type=' . $type;
					//$num = count($objectsInCateg);
					//print_barre_liste($langs->trans(ucfirst($type)), $page, $_SERVER["PHP_SELF"], $param, '', '', '', $num, '', 'object_'.$type.'@dolismq', 0, '', '', $limit);

					$out .= load_fiche_titre($langs->transnoentities($classname), '', 'object_' . $object->picto);
					$out .= '<table class="noborder centpercent">';
					$out .= '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Ref").'</td></tr>';
					if (count($objectsInCateg) > 0) {
						$i = 0;
						foreach ($objectsInCateg as $element) {
							$i++;
							if ($i > $limit) break;

							$out .= '<tr class="oddeven">';
							$out .= '<td class="nowrap" valign="top">';
							$out .= $element->getNomUrl(1);
							$out .= '</td>';
							// Link to delete from category
							$out .= '<td class="right">';
							if ($user->rights->categorie->creer) {
								$out .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=delintocategory&id=' . $id . '&type=' . $type . '&element_id=' . $element->id . '&token=' . newToken() . '">';
								$out .= $langs->trans("DeleteFromCat");
								$out .= img_picto($langs->trans("DeleteFromCat"), 'unlink', '', false, 0, 0, '', 'paddingleft');
								$out .= '</a>';
							}
							$out .= '</td>';
							$out .= '</tr>';
						}
					} else {
						$out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("ThisCategoryHasNoItems").'</td></tr>';
					}
					$out .= '</table>';
				}
			} ?>

			<script>
				jQuery('.fichecenter').last().after(<?php echo json_encode($out) ; ?>)
			</script>
			<?php
		}

		if (!$error) {
			$this->results   = array('myreturn' => 999);
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the redirectAfterConnection function : replacing the parent's function with the one below
	 *
	 * @param $parameters
	 * @return int
	 */
	public function redirectAfterConnection($parameters)
	{
		global $conf;

		if ($parameters['currentcontext'] == 'mainloginpage') {
			if ($conf->global->DOLISMQ_REDIRECT_AFTER_CONNECTION) {
				$value = dol_buildpath('/custom/dolismq/dolismqindex.php?mainmenu=dolismq', 1);
			} else {
				$value = '';
			}
		}

		if (true) {
			$this->resprints = $value;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

    /**
     *  Overloading the saturneBannerTab function : replacing the parent's function with the one below.
     *
     * @param  array        $parameters Hook metadatas (context, etc...).
     * @param  CommonObject $object     Current object.
     * @return int                      0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneBannerTab(array $parameters, CommonObject $object): int
    {
        global $conf, $langs;

        // Do something only for the current context.
        if (preg_match('/controlcard/', $parameters['context'])) {
            if ($conf->browser->layout == 'phone') {
                $morehtmlref = '<i class="toggleControlInfo far fa-caret-square-down"></i>' . ' ' . $langs->trans('DisplayMoreInfo');
            } else {
                $morehtmlref = '';
            }

            $this->resprints = $morehtmlref;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the printMainArea function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function printMainArea(array $parameters): int
    {
        global $conf, $mysoc;

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'publiccontrol') {
            if (!empty($conf->global->SATURNE_SHOW_COMPANY_LOGO)) {
                // Define logo and logoSmall.
                $logoSmall = $mysoc->logo_small;
                $logo      = $mysoc->logo;
                // Define urlLogo.
                $urlLogo = '';
                if (!empty($logoSmall) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logoSmall)) {
                    $urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/thumbs/' . $logoSmall);
                } elseif (!empty($logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo)) {
                    $urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/' . $logo);
                }
                // Output html code for logo.
                if ($urlLogo) {
                    print '<div class="center signature-logo">';
                    print '<img src="' . $urlLogo . '">';
                    print '</div>';
                }
                print '<div class="underbanner clearboth"></div>';
            }
        }

        return 0; // or return 1 to replace standard code.
    }
}
