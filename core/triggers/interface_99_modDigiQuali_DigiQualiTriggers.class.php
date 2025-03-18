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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/triggers/interface_99_modDigiQuali_DigiQualiTriggers.class.php
 * \ingroup digiquali
 * \brief   DigiQuali trigger
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggers for DigiQuali module
 */
class InterfaceDigiQualiTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = 'demo';
        $this->description = 'DigiQuali triggers.';
        $this->version     = '21.0.0';
        $this->picto       = 'digiquali@digiquali';
    }

    /**
     * Function called when a Dolibarr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param  string       $action Event action code
     * @param  CommonObject $object Object
     * @param  User         $user   Object user
     * @param  Translate    $langs  Object langs
     * @param  Conf         $conf   Object conf
     * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK
     * @throws Exception
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        if (!isModEnabled('digiquali')) {
            return 0; // If module is not enabled, we do nothing
        }

        // Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $actionComm = new ActionComm($this->db);

        $triggerType = dol_ucfirst(dol_strtolower(explode('_', $action)[1]));

        $actionComm->code         = 'AC_' . $action;
        $actionComm->type_code    = 'AC_OTH_AUTO';
        $actionComm->fk_element   = $object->id;
        $actionComm->elementtype  = $object->element . '@' . $object->module;
        $actionComm->label        = $langs->transnoentities('Object' . $triggerType . 'Trigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
        $actionComm->datep        = dol_now();
        $actionComm->userownerid  = $user->id;
        $actionComm->percentage   = -1;

        if (getDolGlobalInt('DIGIQUALI_ADVANCED_TRIGGER') && !empty($object->fields)) {
            $actionComm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        $objects      = ['QUESTION', 'SHEET', 'CONTROL', 'SURVEY'];
        $triggerTypes = ['CREATE', 'MODIFY', 'DELETE', 'VALIDATE', 'LOCK', 'ARCHIVE'];
        $extraActions = ['CONTROL_UNVALIDATE', 'SURVEY_UNVALIDATE', 'CONTROL_SENTBYMAIL', 'SURVEY_SENTBYMAIL', 'CONTROL_SAVEANSWER', 'SURVEY_SAVEANSWER', 'SHEET_ADDQUESTION'];

        $actions = array_merge(
            array_merge(...array_map(fn($s) => array_map(fn($p) => "{$p}_{$s}", $objects), $triggerTypes)),
            $extraActions
        );

        if (in_array($action, $actions, true)) {
            $actionComm->create($user);
        }

        switch ($action) {
            case 'QUESTION_CREATE' :
                if (GETPOST('question_group_id') > 0) {
                    $questionGroup = new QuestionGroup($this->db);
                    $questionGroup->fetch(GETPOST('question_group_id'));
                    $questionGroup->addQuestion($object->id);
                } else if (GETPOST('sheet_id') > 0) {
                   $sheet = new Sheet($this->db);
                   $object->add_object_linked('digiquali_' . $sheet->element,GETPOST('sheet_id'));

                   $sheet->updateQuestionsAndGroupsPosition([], [], true);

                   $sheet->call_trigger('SHEET_ADDQUESTION', $user);
               }
            break;
            case 'SURVEY_CREATE' :
                // Load Digiquali libraries
                require_once __DIR__ . '/../../class/sheet.class.php';

                $sheet = new Sheet($this->db);

                $sheet->fetch($object->fk_sheet);
                if ($sheet->success_rate > 0) {
                    $object->success_rate = $sheet->success_rate;
                    $object->setValueFrom('success_rate', $object->success_rate, '', '', 'text', '', $user);
                }

                if ($object->context != 'createfromclone') {
                    $elementArray = get_sheet_linkable_objects();
                    if (!empty($elementArray)) {
                        foreach ($elementArray as $linkableElement) {
                            if (!empty(GETPOST($linkableElement['post_name'])) && GETPOST($linkableElement['post_name']) > 0) {
                                $object->add_object_linked($linkableElement['link_name'], GETPOST($linkableElement['post_name']));
                            }
                        }
                    }
                }

                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;
            case 'ANSWER_CREATE' :
            case 'ANSWER_MODIFY' :
            case 'ANSWER_DELETE' :
                $actionComm->fk_element  = $object->fk_question;
                $actionComm->elementtype = 'question@' . $object->module;
                $actionComm->create($user);
                break;

            case 'CONTROLDOCUMENT_GENERATE' :
            case 'SURVEYDOCUMENT_GENERATE' :
                $actionComm->fk_element  = $object->parent_id;
                $actionComm->elementtype = $object->parent_type . '@' . $object->module;
                $actionComm->create($user);
                break;
        }

        return 0;
    }
}
