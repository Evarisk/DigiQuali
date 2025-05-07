<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \brief   DigiQuali hook overload
 */

/**
 * Class ActionsDigiquali
 */
class ActionsDigiquali
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string|null String displayed by executeHook() immediately after return
     */
    public ?string $resprints;

    /**
     * Constructor
     *
     *  @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the constructCategory function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     The object to process
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function constructCategory(array $parameters, &$object): int
    {
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

            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($parameters['context'], 'categoryindex') !== false) {
            $resourcesRequired = [
                'js'  => '/custom/digiquali/js/digiquali.js'
            ];

            $out  = '<!-- Includes JS added by module digiquali -->';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['js'], 1) . '"></script>';

            $this->resprints = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the hookSetManifest function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function hookSetManifest(array $parameters): int
    {
        if (strpos($_SERVER['PHP_SELF'], 'digiquali') !== false) {
            $this->resprints = dol_buildpath('custom/digiquali/manifest.json.php', 1);
            return 1; // or return 1 to replace standard code
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @param  object $object     The object to process
     * @param  string $action     Current action (if set). Generally create or edit or null
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $conf;

        if (strpos($parameters['context'], 'categorycard') !== false) {
            require_once __DIR__ . '/../class/question.class.php';
            require_once __DIR__ . '/../class/sheet.class.php';
            require_once __DIR__ . '/../class/control.class.php';
            require_once __DIR__ . '/../class/survey.class.php';
        }

        if (strpos($parameters['context'], 'productlotcard') !== false) {
            if (isModEnabled('easyurl') && $action == 'set_easy_url_link') {
                //set_easy_url_link($object);

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formObjectOptions(array $parameters, ?object $object): int
    {
        global $conf, $extrafields, $langs, $user;

        require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';

        $linkableElements = get_sheet_linkable_objects();
        if (!empty($linkableElements)) {
            foreach($linkableElements as $linkableElement) {
                if ($linkableElement['tab_type'] == $object->element) {
                    if (strpos($parameters['context'], $linkableElement['hook_name_card']) !== false) {
                        $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                        $extraFieldsNames = ['qc_frequency', 'control_history_link'];
                        foreach ($extraFieldsNames as $extraFieldsName) {
                            $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                        }
                    }
                }
            }
        }

        if (strpos($parameters['context'], 'productlotcard') !== false) {
            $objectB64                 = base64_encode(json_encode(['type' => $object->element, 'id' => (int) $object->id]));
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control_history.php?track_id=' . $objectB64 . '&entity=' . $conf->entity, 3);
            $setEasyUrlLinkButton      =  '';
            $assignEasyUrlButton       = '';
            if (isModEnabled('easyurl')) {
                require_once DOL_DOCUMENT_ROOT . '/custom/easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch('', '', ' AND t.original_url = "' . $publicControlInterfaceUrl . '"');
                if ($result > 0) {
                    $publicControlInterfaceUrl = $shortener->short_url;
                } else {
                    if ($user->hasRight('easyurl', 'shortener', 'write')) {
                        $setEasyUrlLinkButton .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_easy_url_link&token=' . newToken() . '">';
                        $setEasyUrlLinkButton .= img_picto($langs->trans('SetEasyURLLink'), 'fontawesome_fa-redo_fas_#444', 'class="paddingright pictofixedwidth valignmiddle"') . '</a>';
                        $setEasyUrlLinkButton .= '<span>' . img_picto($langs->trans('GetEasyURLErrors'), 'fontawesome_fa-exclamation-triangle_fas_#bc9526') . '</span>';
                    }
                    if ($user->hasRight('easyurl', 'shortener', 'assign')) {
                        //@Todo assign button
                        //$assignEasyUrlButton .= dolButtonToOpenUrlInDialogPopup('assignShortener', $langs->transnoentities('AssignShortener'), '<span class="fas fa-link" title="' . $langs->trans('Assign') . '"></span>', '/custom/easyurl/view/shortener/shortener_card.php?element_type=' . $object->element . '&fk_element=' . $object->id . '&from_element=1&original_url=' . $publicControlInterfaceUrl . '&action=edit_assign', '', 'btnTitle', 'window.saturne.toolbox.checkIframeCreation();') . '</td>';
                    }
                }
            }

            $out  = '<a href="' . $publicControlInterfaceUrl . '" target="_blank" title="URL : ' . $publicControlInterfaceUrl . '"><i class="fas fa-external-link-alt paddingrightonly"></i>' . dol_trunc($publicControlInterfaceUrl) . '</a>';
            $out .= showValueWithClipboardCPButton($publicControlInterfaceUrl, 0, 'none');
//            $out .= $setEasyUrlLinkButton;
//            $out .= $assignEasyUrlButton;

            $object->array_options['options_control_history_link'] = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListOption(array $parameters, $object): int
    {
        global $extrafields, $langs;

        require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';

        $linkableElements = get_sheet_linkable_objects();
        if (!empty($linkableElements)) {
            foreach($linkableElements as $linkableElement) {
                if ($linkableElement['tab_type'] == $object->element) {
                    if (preg_match('/' . $linkableElement['hook_name_list'] . '|projecttaskscard/', $parameters['context'])) {
                        $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                        $extraFieldsNames = ['qc_frequency', 'control_history_link'];
                        foreach ($extraFieldsNames as $extraFieldsName) {
                            $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                        }
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListValue function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadata (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListValue(array $parameters): int
    {
        global $conf, $langs, $user;

        if (strpos($parameters['context'], 'product_lotlist') !== false) {
            $trackId                   = base64_encode(json_encode(['type' => $parameters['object']->element, 'id' => (int) $parameters['object']->id]));
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control_history.php?track_id=' . $trackId . '&entity=' . $conf->entity, 3);
            $setEasyUrlLinkButton      =  '';
            $assignEasyUrlButton       = '';
            if (isModEnabled('easyurl')) {
                require_once DOL_DOCUMENT_ROOT . '/custom/easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch('', '', ' AND t.original_url = "' . $publicControlInterfaceUrl . '"');
                if ($result > 0) {
                    $publicControlInterfaceUrl = $shortener->short_url;
                } else {
                    if ($user->hasRight('easyurl', 'shortener', 'write')) {
                        $setEasyUrlLinkButton .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $parameters['object']->id . '&action=set_easy_url_link&token=' . newToken() . '">';
                        $setEasyUrlLinkButton .= img_picto($langs->trans('SetEasyURLLink'), 'fontawesome_fa-redo_fas_#444', 'class="paddingright pictofixedwidth valignmiddle"') . '</a>';
                        $setEasyUrlLinkButton .= '<span>' . img_picto($langs->trans('GetEasyURLErrors'), 'fontawesome_fa-exclamation-triangle_fas_#bc9526') . '</span>';
                    }
                    if ($user->hasRight('easyurl', 'shortener', 'assign')) {
                        //@Todo assign button
                        //$assignEasyUrlButton .= dolButtonToOpenUrlInDialogPopup('assignShortener', $langs->transnoentities('AssignShortener'), '<span class="fas fa-link" title="' . $langs->trans('Assign') . '"></span>', '/custom/easyurl/view/shortener/shortener_card.php?element_type=' . $parameters['object']->element . '&fk_element=' . $parameters['object']->id . '&from_element=1&original_url=' . $publicControlInterfaceUrl . '&action=edit_assign', '', 'btnTitle', 'window.saturne.toolbox.checkIframeCreation();') . '</td>';
                    }
                }
            }

            $out  = '<a href="' . $publicControlInterfaceUrl . '" target="_blank" title="URL : ' . $publicControlInterfaceUrl . '"><i class="fas fa-external-link-alt paddingrightonly"></i>' . dol_trunc($publicControlInterfaceUrl) . '</a>';
            $out .= showValueWithClipboardCPButton($publicControlInterfaceUrl, 0, 'none');
//            $out .= $setEasyUrlLinkButton;
//            $out .= $assignEasyUrlButton; ?>
            <script>
                var outJS             = <?php echo json_encode($out); ?>;
                var publicControlCell = $('.liste > tbody > tr.oddeven').find('td[data-key="product_lot.control_history_link"]').last();
                publicControlCell.html(outJS);
            </script>
            <?php
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the redirectAfterConnection function : replacing the parent's function with the one below
     *
     * @param array $parameters Hook metadata (context, etc...)
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
    */
    public function redirectAfterConnection(array $parameters): int
    {
        if (strpos($parameters['context'], 'mainloginpage') !== false) {
            if (getDolGlobalInt('DIGIQUALI_REDIRECT_AFTER_CONNECTION')) {
                $this->resprints = dol_buildpath('/custom/digiquali/digiqualiindex.php?mainmenu=digiquali', 1);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the completeTabsHead function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    function completeTabsHead(array $parameters, $object) : int
    {
        global $langs;

        if (strpos($parameters['context'], 'main') !== false) {
            if (!empty($parameters['head'])) {
                foreach ($parameters['head'] as $headKey => $headTab) {
                    if (is_array($headTab) && count($headTab) > 0) {
                        if (isset($headTab[2]) && $headTab[2] === 'control' && is_string($headTab[1]) && strpos($headTab[1], $langs->trans('Controls')) !== false && strpos($headTab[1], 'badge') === false) {
                            if ($object->element == 'productlot') {
                                $sourceType = 'productbatch';
                            } else {
                                $sourceType = $object->element;
                            }
                            $object->fetchObjectLinked($object->id, $sourceType, null, 'digiquali_control', 'OR', 1, 'sourcetype', 0);
                            if (isset($object->linkedObjectsIds['digiquali_control']) && !empty($object->linkedObjectsIds['digiquali_control'])) {
                                $NbControls = count($object->linkedObjectsIds['digiquali_control']);
                                $parameters['head'][$headKey][1] .= '<span class="badge marginleftonlyshort">' . $NbControls . '</span>';
                            }
                        }
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, CommonObject $object): int
    {
        global $conf, $langs;

        if (preg_match('/controlcard|surveycard/', $parameters['context'])) {
            if ($conf->browser->layout == 'phone') {
                $this->resprints = '<br><div>' . img_picto('', 'fontawesome_fa-caret-square-down_far_#966EA2F2_fa-2em', 'class="toggle-object-infos pictofixedwidth valignmiddle" style="width: 35px;"') . $langs->trans('DisplayMoreInfo') . '</div>';
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneAdminDocumentData function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneAdminDocumentData(array $parameters): int
    {
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

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneAdminObjectConst function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneAdminObjectConst(array $parameters): int
    {
        if (strpos($parameters['context'], 'surveyadmin') !== false) {
            $constArray['digiquali'] = [
                'DisplayMedias' => [
                    'name'        => 'DisplayMediasSample',
                    'description' => 'DisplaySurveyMediasSampleDescription',
                    'code'        => 'DIGIQUALI_SURVEY_DISPLAY_MEDIAS',
                ],
                'UseLargeSizeMedia' => [
                    'name'        => 'UseLargeSizeMedia',
                    'description' => 'UseLargeSizeMediaDescription',
                    'code'        => 'DIGIQUALI_SURVEY_USE_LARGE_MEDIA_IN_GALLERY',
                ],
                'AutoSaveActionQuestionAnswer' => [
                    'name'        => 'AutoSaveActionQuestionAnswer',
                    'description' => 'AutoSaveActionQuestionAnswerDescription',
                    'code'        => 'DIGIQUALI_SURVEYDET_AUTO_SAVE_ACTION',
                ]
            ];
            $this->results = $constArray;

            return 1; // or return 1 to replace standard code
        }

        if (strpos($parameters['context'], 'controladmin') !== false) {
            $constArray['digiquali'] = [
                'DisplayMedias' => [
                    'name'        => 'DisplayMediasSample',
                    'description' => 'DisplayMediasSampleDescription',
                    'code'        => 'DIGIQUALI_CONTROL_DISPLAY_MEDIAS',
                ],
                'UseLargeSizeMedia' => [
                    'name'        => 'UseLargeSizeMedia',
                    'description' => 'UseLargeSizeMediaDescription',
                    'code'        => 'DIGIQUALI_CONTROL_USE_LARGE_MEDIA_IN_GALLERY',
                ],
                'LockControlOutdatedEquipment' => [
                    'name'        => 'LockControlOutdatedEquipment',
                    'description' => 'LockControlOutdatedEquipmentDescription',
                    'code'        => 'DIGIQUALI_LOCK_CONTROL_OUTDATED_EQUIPMENT',
                ],
                'AutoSaveActionQuestionAnswer' => [
                    'name'        => 'AutoSaveActionQuestionAnswer',
                    'description' => 'AutoSaveActionQuestionAnswerDescription',
                    'code'        => 'DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION',
                ],
                'EnablePublicControlHistory' => [
                    'name'        => 'EnablePublicControlHistory',
                    'description' => 'EnablePublicControlHistoryDescription',
                    'code'        => 'DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY',
                ],
                'ShowQcFrequencyPublicInterface' => [
                    'name'        => 'ShowQcFrequencyPublicInterface',
                    'description' => 'ShowQcFrequencyPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_QC_FREQUENCY_PUBLIC_INTERFACE',
                ],
                'ShowLastControlFirstOnPublicHistory' => [
                    'name'        => 'ShowLastControlFirstOnPublicHistory',
                    'description' => 'ShowLastControlFirstOnPublicHistoryDescription',
                    'code'        => 'DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY',
                ],
                'ShowAddControlButtonOnPublicInterface' => [
                    'name'        => 'ShowAddControlButtonOnPublicInterface',
                    'description' => 'ShowAddControlButtonOnPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_ADD_CONTROL_BUTTON_ON_PUBLIC_INTERFACE',
                ],
                'ShowParentLinkedObjectOnPublicInterface' => [
                    'name'        => 'ShowParentLinkedObjectOnPublicInterface',
                    'description' => 'ShowParentLinkedObjectOnPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_PARENT_LINKED_OBJECT_ON_PUBLIC_INTERFACE',
                ]
            ];
            $this->results = $constArray;

            return 1; // or return 1 to replace standard code
        }

        return 0; // or return 1 to replace standard code
    }
}
