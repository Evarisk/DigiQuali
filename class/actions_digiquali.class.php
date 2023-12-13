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
 * \file    class/actions_digiquali.class.php
 * \ingroup digiquali
 * \brief   DigiQuali hook overload.
 */

/**
 * Class ActionsDigiquali
 */
class ActionsDigiquali
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
					'obj_table' => 'digiquali_question',
				],
				'sheet' => [
					'id'        => 436301002,
					'code'      => 'sheet',
					'obj_class' => 'Sheet',
					'obj_table' => 'digiquali_sheet',
				],
				'control' => [
					'id'        => 436301003,
					'code'      => 'control',
					'obj_class' => 'Control',
					'obj_table' => 'digiquali_control',
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
			if ($id > 0 && $elementId > 0 && ($type == 'question' || $type == 'sheet' || $type == 'control') && $user->rights->digiquali->$type->write) {
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
		global $conf, $form, $langs, $object, $user;

		$error = 0; // Error counter

		if (preg_match('/categoryindex/', $parameters['context'])) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			print '<script src="../custom/digiquali/js/digiquali.js"></script>';
		} elseif (preg_match('/categorycard/', $parameters['context']) && preg_match('/viewcat.php/', $_SERVER["PHP_SELF"])) {
            require_once __DIR__ . '/../class/question.class.php';
            require_once __DIR__ . '/../class/sheet.class.php';
            require_once __DIR__ . '/../class/control.class.php';
        } elseif ($parameters['currentcontext'] == 'productlotcard') {
            $productLot = new ProductLot($this->db);
            $productLot->fetch(GETPOST('id'));
            $objectB64 = $productLot->array_options['options_control_history_link'];
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control_history.php?track_id=' . $objectB64 . '&entity=' . $conf->entity, 3);

            $out = showValueWithClipboardCPButton($publicControlInterfaceUrl, 0, '&nbsp;');
            $out .= '<a target="_blank" href="'. $publicControlInterfaceUrl .'"><div class="butAction">';
            $out .= '<i class="fa fa-external-link"></i>';
            $out .= '</div></a>'; ?>

            <script>
                $('[class*=extras_control_history_link]').html(<?php echo json_encode($out) ?>);
            </script>
            <?php
        }

        require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';

        $linkableElements = get_sheet_linkable_objects();

        if (!empty($linkableElements)) {
            foreach($linkableElements as $linkableElement) {
                if ($linkableElement['link_name'] == $object->element) {
                    if ($parameters['currentcontext'] == $linkableElement['hook_name_card']) {
                        $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                        $extrafieldsNames = ['qc_frequency', 'control_history_link'];
                        foreach ($extrafieldsNames as $extrafieldsName) {
                            $jQueryElement = 'td.' . $object->element . '_extras_' . $extrafieldsName; ?>
                            <script>
                                var objectElement = <?php echo "'" . $jQueryElement . "'"; ?>;
                                jQuery(objectElement).prepend(<?php echo json_encode($picto); ?>);
                            </script>
                            <?php
                        }
                    } elseif (in_array($parameters['currentcontext'], [$linkableElement['hook_name_list'], 'projecttaskscard']) || preg_match('/' . $linkableElement['hook_name_list'] . '/', $parameters['context'])) {
                        $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                        $extrafieldsNames = ['qc_frequency', 'control_history_link'];
                        foreach ($extrafieldsNames as $extrafieldsName) { ?>
                            <script>
                                var objectElement = <?php echo "'" . $extrafieldsName . "'"; ?>;
                                var outJS         = <?php echo json_encode($picto); ?>;
                                var cell          = $('.liste > tbody > tr.liste_titre').find('th[data-titlekey="' + objectElement + '"]');
                                cell.prepend(outJS);
                            </script>
                            <?php
                        }
                    }
                }
            }
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
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param array $parameters Hook metadata (context, etc...)
     * @param object $object     Object
     * @return void
     */
    public function formObjectOptions(array $parameters, object $object) {
        if ($parameters['currentcontext'] == 'productlotcard') {
            $objectData = ['type' => $object->element, 'id' => $object->id];

            $objectDataJson = json_encode($objectData);
            $objectDataB64  = base64_encode($objectDataJson);

            if (dol_strlen($object->array_options['options_control_history_link'] == 0 )) {
                $object->array_options['options_control_history_link'] = $objectDataB64;
                $object->updateExtrafield('control_history_link');
            }
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
			if ($conf->global->DIGIQUALI_REDIRECT_AFTER_CONNECTION) {
				$value = dol_buildpath('/custom/digiquali/digiqualiindex.php?mainmenu=digiquali', 1);
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
                $morehtmlref = '<br><div>' . img_picto('', 'fontawesome_fa-caret-square-down_far_#966EA2F2_fa-2em', 'class="toggleControlInfo pictofixedwidth valignmiddle" style="width: 35px;"') . $langs->trans('DisplayMoreInfo') . '</div>';
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
        if (in_array($parameters['currentcontext'], ['publiccontrol', 'publicsurvey', 'publiccontrolhistory'])) {
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

    /**
     * Overloading the SaturneAdminDocumentData function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function SaturneAdminDocumentData($parameters)
    {
        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'digiqualiadmindocuments') {
            $types = [
                'ControlDocument' => [
                    'documentType' => 'controldocument',
                    'picto'        => 'fontawesome_fa-tasks_fas_#d35968'
                ]
            ];
            $this->results = $types;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the SaturneAdminObjectConst function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function SaturneAdminObjectConst(array $parameters): int
    {
        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'digiqualiadmindocuments') {
            $constArray['digiquali'] = [
                'controldocument' => [
                    'name'        => 'ControlDocumentDisplayMedias',
                    'description' => 'ControlDocumentDisplayMediasDescription',
                    'code'        => 'DIGIQUALI_CONTROLDOCUMENT_DISPLAY_MEDIAS'
                ]
            ];
            $this->results = $constArray;
            return 1;
        }

        return 0; // or return 1 to replace standard code.
    }
}
