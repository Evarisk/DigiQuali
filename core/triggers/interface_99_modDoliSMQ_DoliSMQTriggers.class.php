<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    core/triggers/interface_99_modDoliSMQ_DoliSMQTriggers.class.php
 * \ingroup dolismq
 * \brief   DoliSMQ trigger.
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for DoliSMQ module
 */
class InterfaceDoliSMQTriggers extends DolibarrTriggers
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
	public function __construct($db)
	{
		parent::__construct($db);

		$this->name        = preg_replace('/^Interface/i', '', get_class($this));
		$this->family      = 'demo';
		$this->description = 'DoliSMQ triggers.';
		$this->version     = '1.6.0';
		$this->picto       = 'dolismq@dolismq';
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
		if (!isModEnabled('dolismq')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Data and type of action are stored into $object and $action
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
		$now = dol_now();
		$actioncomm = new ActionComm($this->db);

		$actioncomm->elementtype = $object->element . '@dolismq';
		$actioncomm->type_code   = 'AC_OTH_AUTO';
		$actioncomm->datep       = $now;
		$actioncomm->fk_element  = $object->id;
		$actioncomm->userownerid = $user->id;
		$actioncomm->percentage  = -1;

		switch ($action) {
			case 'QUESTION_CREATE' :
			case 'SHEET_CREATE' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
				$actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'ANSWER_CREATE' :
				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->fk_element  = $object->fk_question;
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
				$actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'CONTROL_CREATE' :
                if ($object->context != 'createfromclone') {
					$linkableElements = get_sheet_linkable_objects();

					if (is_array($linkableElements) && !empty($linkableElements)) {
						foreach ($linkableElements as $linkableElementType => $linkableElement) {
							if (!empty(GETPOST($linkableElement['post_name'])) && GETPOST($linkableElement['post_name']) > 0) {
								$object->add_object_linked($linkableElement['link_name'], GETPOST($linkableElement['post_name']));
							}
						}
					}

                    // Load Saturne libraries.
                    require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

                    $signatory = new SaturneSignature($this->db, 'dolismq');
                    $signatory->setSignatory($object->id, $object->element, 'user', [$object->fk_user_controller], 'Controller', 1);
                }

				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
				$actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'QUESTION_MODIFY' :
			case 'SHEET_MODIFY' :
			case 'CONTROL_MODIFY' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
				$actioncomm->label = $langs->transnoentities('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'ANSWER_MODIFY' :
				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->fk_element  = $object->fk_question;
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
				$actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'QUESTION_DELETE' :
			case 'SHEET_DELETE' :
			case 'CONTROL_DELETE' :
				$actioncomm->code  = 'AC_ ' . strtoupper($object->element) . '_DELETE';
				$actioncomm->label = $langs->transnoentities('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'ANSWER_DELETE' :
				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->fk_element  = $object->fk_question;
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_DELETE';
				$actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'QUESTION_VALIDATE' :
			case 'SHEET_VALIDATE' :
			case 'CONTROL_VALIDATE' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_VALIDATE';
				$actioncomm->label = $langs->transnoentities('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'CONTROL_UNVALIDATE' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_UNVALIDATE';
				$actioncomm->label = $langs->transnoentities('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'QUESTION_LOCKED' :
			case 'SHEET_LOCKED' :
			case 'CONTROL_LOCKED' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_LOCKED';
				$actioncomm->label = $langs->transnoentities('ObjectLockedTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'CONTROL_SENTBYMAIL' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_SENTBYMAIL';
				$actioncomm->label = $langs->transnoentities('ObjectSentByMailTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;
		}
		return 0;
	}
}
