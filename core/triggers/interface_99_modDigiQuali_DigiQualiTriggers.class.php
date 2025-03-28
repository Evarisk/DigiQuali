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
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc(): string
    {
        return parent::getDesc();
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
        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype  = $object->element . '@digiquali';
        $actioncomm->type_code    = 'AC_OTH_AUTO';
        $actioncomm->datep        = $now;
        $actioncomm->fk_element   = $object->id;
        $actioncomm->userownerid  = $user->id;
        $actioncomm->percentage   = -1;

        if ($conf->global->DIGIQUALI_ADVANCED_TRIGGER && !empty($object->fields)) {
            $actioncomm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        switch ($action) {
            case 'QUESTION_CREATE' :
            case 'SHEET_CREATE' :
                $object->fetch($object->id);
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'ANSWER_CREATE' :
                $object->fetch($object->id);
                $actioncomm->elementtype = 'question@digiquali';
                $actioncomm->fk_element  = $object->fk_question;
                $actioncomm->code        = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label       = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'CONTROL_CREATE' :
                // Load Digiquali libraries
                require_once __DIR__ . '/../../class/sheet.class.php';

                $sheet = new Sheet($this->db);

                $sheet->fetch($object->fk_sheet);
                if ($sheet->success_rate > 0) {
                    $object->success_rate = $sheet->success_rate;
                    $object->setValueFrom('success_rate', $object->success_rate, '', '', 'text', '', $user);
                }

                $elementArray = [];
                if ($object->context != 'createfromclone') {
                    $elementArray = get_sheet_linkable_objects();
                    if (!empty($elementArray)) {
                        foreach ($elementArray as $linkableElementType => $linkableElement) {
                            if (!empty(GETPOST($linkableElement['post_name'])) && GETPOST($linkableElement['post_name']) > 0) {
                                $object->add_object_linked($linkableElement['link_name'], GETPOST($linkableElement['post_name']));
                            }
                        }
                    }

                    // Load Saturne libraries.
                    require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

                    $signatory = new SaturneSignature($this->db, 'digiquali');
                    $signatory->setSignatory($object->id, $object->element, 'user', [$object->fk_user_controller], 'Controller', 1);
                }

                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
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

            case 'QUESTION_MODIFY' :
            case 'SHEET_MODIFY' :
            case 'CONTROL_MODIFY' :
            case 'SURVEY_MODIFY' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
                $actioncomm->label = $langs->transnoentities('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'ANSWER_MODIFY' :
                $actioncomm->elementtype = 'question@digiquali';
                $actioncomm->fk_element  = $object->fk_question;
                $actioncomm->code        = 'AC_' . strtoupper($object->element) . '_MODIFY';
                $actioncomm->label       = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'QUESTION_DELETE' :
            case 'SHEET_DELETE' :
            case 'CONTROL_DELETE' :
            case 'SURVEY_DELETE' :
                $actioncomm->code  = 'AC_ ' . strtoupper($object->element) . '_DELETE';
                $actioncomm->label = $langs->transnoentities('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'ANSWER_DELETE' :
                $actioncomm->elementtype = 'question@digiquali';
                $actioncomm->fk_element  = $object->fk_question;
                $actioncomm->code        = 'AC_' . strtoupper($object->element) . '_DELETE';
                $actioncomm->label       = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'QUESTION_VALIDATE' :
            case 'SHEET_VALIDATE' :
            case 'CONTROL_VALIDATE' :
            case 'SURVEY_VALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_VALIDATE';
                $actioncomm->label = $langs->transnoentities('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'CONTROL_UNVALIDATE' :
            case 'SURVEY_UNVALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_UNVALIDATE';
                $actioncomm->label = $langs->transnoentities('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'QUESTION_LOCK' :
            case 'SHEET_LOCK' :
            case 'SURVEY_LOCK' :
                $actioncomm->code          = 'AC_' . strtoupper($object->element) . '_LOCK';
                $actioncomm->label         = $langs->transnoentities('ObjectLockedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->note_private .= $langs->trans('Status') . ' : ' . $langs->trans('Locked') . '</br>';
                $actioncomm->create($user);
                break;

            case 'CONTROL_LOCK' :
                $actioncomm->code          = 'AC_' . strtoupper($object->element) . '_LOCK';
                $actioncomm->label         = $langs->transnoentities('ObjectLockedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->note_private .= $langs->trans('Status') . ' : ' . $langs->trans('Locked') . '</br>';
                $actioncomm->create($user);

                $actioncommID = 0;
                $elementArray = get_sheet_linkable_objects();
                $object->fetchObjectLinked('', '', '', 'digiquali_control', 'OR', 1, 'sourcetype', 0);
                if (!empty($elementArray)) {
                    foreach($elementArray as $linkableElement) {
                        if ($linkableElement['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableElement['link_name']]))) {
                            $className    = $linkableElement['className'];
                            $linkedObject = new $className($this->db);

                            $linkedObjectKey = array_key_first($object->linkedObjectsIds[$linkableElement['link_name']]);
                            $linkedObjectId  = $object->linkedObjectsIds[$linkableElement['link_name']][$linkedObjectKey];

                            $result = $linkedObject->fetch($linkedObjectId);

                            if ($result > 0) {
                                $linkedObject->fetch_optionals();
                                if (!empty($linkedObject->array_options['options_qc_frequency'])) {
                                    $qcFrequency = $linkedObject->array_options['options_qc_frequency'];

                                    if (dol_strlen($object->next_control_date) <= 0) {
                                        if ($object->verdict == 2) {
                                            $object->next_control_date = $this->db->idate($now);
                                        } else {
                                            $object->next_control_date = $this->db->idate(dol_time_plus_duree($now, $qcFrequency, 'd'));
                                        }
                                    }
                                    $object->status = $object::STATUS_LOCKED;
                                    $object->update($user, true);

                                    $actioncomm->code        = 'AC_' . strtoupper($object->element) . '_REMINDER';
                                    $actioncomm->label       = $langs->transnoentities('ControlReminderTrigger', $langs->transnoentities(ucfirst($linkedObject->element)) . ' ' . $linkedObject->ref, $qcFrequency);
                                    $actioncomm->elementtype = $linkedObject->element;
                                    $actioncomm->datep       = dol_time_plus_duree($now, $qcFrequency, 'd');
                                    $actioncomm->fk_element  = $linkedObject->id;
                                    $actioncomm->userownerid = $user->id;
                                    $actioncomm->percentage  = ActionComm::EVENT_TODO;
                                    $actioncommID            = $actioncomm->create($user);
                                }
                                if (dol_strlen($object->control_date) <= 0) {
                                    $object->control_date = $this->db->idate($now);
                                    $object->setValueFrom('control_date', $object->control_date, '', '', 'date', '', $user);
                                }
                            }
                        }
                    }
                }

                // Create reminders
                if ($actioncommID > 0 && !empty($object->next_control_date) && getDolGlobalInt('DIGIQUALI_CONTROL_REMINDER_ENABLED') && (getDolGlobalString('AGENDA_REMINDER_BROWSER') || getDolGlobalString('AGENDA_REMINDER_EMAIL'))) {
                    $actionCommReminder = new ActionCommReminder($this->db);

                    $actionCommReminder->status        = ActionCommReminder::STATUS_TODO;
                    $actionCommReminder->fk_actioncomm = $actioncommID;
                    $actionCommReminder->fk_user       = $user->id;

                    $nextControlDate = is_int($object->next_control_date) ? $object->next_control_date : dol_stringtotime($object->next_control_date);
                    $nextControl     = floor($nextControlDate - dol_now('tzuser'))/(3600 * 24);
                    $reminderArray   = explode(',' , getDolGlobalString('DIGIQUALI_CONTROL_REMINDER_FREQUENCY'));
                    foreach ($reminderArray as $reminder) {
                        if ($nextControl >= $reminder) {
                            $dateReminder = dol_time_plus_duree($nextControlDate, -$reminder, 'd');

                            $actionCommReminder->dateremind  = $dateReminder;
                            $actionCommReminder->offsetvalue = $reminder;
                            $actionCommReminder->offsetunit  = 'd';
                            $actionCommReminder->typeremind  = getDolGlobalString('DIGIQUALI_CONTROL_REMINDER_TYPE');
                            $actionCommReminder->create($user);
                        }
                    }
                }
                break;

            case 'QUESTION_ARCHIVE' :
            case 'SHEET_ARCHIVE' :
            case 'CONTROL_ARCHIVE' :
            case 'SURVEY_ARCHIVE' :
                $actioncomm->code          = 'AC_' . strtoupper($object->element) . '_ARCHIVE';
                $actioncomm->label         = $langs->transnoentities('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->note_private .= $langs->trans('Status') . ' : ' . $langs->trans('Archived') . '</br>';
                $actioncomm->create($user);
                break;

            case 'SHEET_ADDQUESTION':
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_ADDQUESTION';
                $actioncomm->label = $langs->transnoentities('ObjectAddQuestionTrigger');
                $actioncomm->create($user);
                break;

            case 'OBJECT_SAVEANSWER' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_SAVEANSWER';
                $actioncomm->label = $langs->transnoentities('AnswerSaveTrigger');
                $actioncomm->create($user);
                break;

            case 'CONTROL_VERDICT' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_VERDICT';
                $actioncomm->label = $langs->transnoentities('ObjectSetVerdictTrigger', $object->fields['verdict']['arrayofkeyval'][$object->verdict]);
                $actioncomm->create($user);
                break;

            case 'CONTROL_SENTBYMAIL' :
            case 'SURVEY_SENTBYMAIL' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_SENTBYMAIL';
                $actioncomm->label = $langs->transnoentities('ObjectSentByMailTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'CONTROLDOCUMENT_GENERATE' :
            case 'SURVEYDOCUMENT_GENERATE' :
                $actioncomm->elementtype = $object->parent_type . '@digiquali';
                $actioncomm->fk_element  = $object->parent_id;
                $actioncomm->code        = 'AC_' . strtoupper($object->element) . '_GENERATE';
                $actioncomm->label       = $langs->transnoentities('ObjectGenerateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;
        }
        return 0;
    }
}
