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

		if (strpos($parameters['context'], 'category') !== false) {
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
				],
                'survey' => [
                    'id'        => 436301004,
                    'code'      => 'survey',
                    'obj_class' => 'Survey',
                    'obj_table' => 'digiquali_survey',
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

		if (strpos($parameters['context'], 'categorycard') !== false) {
            require_once __DIR__ . '/../class/question.class.php';
            require_once __DIR__ . '/../class/sheet.class.php';
            require_once __DIR__ . '/../class/control.class.php';
            require_once __DIR__ . '/../class/survey.class.php';
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
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($_SERVER['PHP_SELF'], 'digiquali') !== false) {
            ?>
            <script>
                $('link[rel="manifest"]').remove();
            </script>
            <?php

            $this->resprints = '<link rel="manifest" href="' . DOL_URL_ROOT . '/custom/digiquali/manifest.json.php' . '" />';
        }

        return 0; // or return 1 to replace standard code-->
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

		if (strpos($parameters['context'], 'categoryindex') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			print '<script src="../custom/digiquali/js/digiquali.js"></script>';
		} elseif (strpos($parameters['context'], 'productlotcard') !== false) {
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
                    if (strpos($parameters['context'], $linkableElement['hook_name_card']) !== false) {
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
                    } elseif (preg_match('/' . $linkableElement['hook_name_list'] . '|projecttaskscard/', $parameters['context'])) {
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
        if (strpos($parameters['context'], 'productlotcard') !== false) {
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

		if (strpos($parameters['context'], 'mainloginpage') !== false) {
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
        if (strpos($parameters['context'], 'controlcard') !== false) {
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
        if (preg_match('/publiccontrol|publicsurvey|publiccontrolhistory/', $parameters['context'])) {
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
     * Overloading the saturneAdminDocumentData function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneAdminDocumentData(array $parameters): int
    {
        // Do something only for the current context.
        if (strpos($parameters['context'], 'digiqualiadmindocuments') !== false) {
            $types = [
                'ControlDocument' => [
                    'documentType' => 'controldocument',
                    'picto'        => 'fontawesome_fa-tasks_fas_#d35968'
                ],
                'SurveyDocument' => [
                    'documentType' => 'surveydocument',
                    'picto'        => 'fontawesome_fa-marker_fas_#d35968'
                ]
            ];
            $this->results = $types;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the saturneAdminObjectConst function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneAdminObjectConst(array $parameters): int
    {
        // Do something only for the current context.
        if (strpos($parameters['context'], 'digiqualiadmindocuments') !== false) {
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
