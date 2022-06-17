<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
		$this->db = $db;

		$this->name        = preg_replace('/^Interface/i', '', get_class($this));
		$this->family      = "demo";
		$this->description = "DoliSMQ triggers.";
		$this->version     = '1.2.0';
		$this->picto       = 'dolismq@dolismq';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->dolismq->enabled)) return 0; // If module is not enabled, we do nothing

		// Data and type of action are stored into $object and $action

		switch ($action) {
			case 'QUESTION_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->code        = 'AC_QUESTION_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('QuestionCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'QUESTION_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->code        = 'AC_QUESTION_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('QuestionModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'QUESTION_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->code        = 'AC_QUESTION_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('QuestionDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'QUESTION_LOCKED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'question@dolismq';
				$actioncomm->code        = 'AC_QUESTION_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('QuestionLockedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'SHEET_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'sheet@dolismq';
				$actioncomm->code        = 'AC_SHEET_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('SheetCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'SHEET_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'sheet@dolismq';
				$actioncomm->code        = 'AC_SHEET_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('SheetModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'SHEET_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'sheet@dolismq';
				$actioncomm->code        = 'AC_SHEET_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('SheetDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'SHEET_LOCKED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'sheet@dolismq';
				$actioncomm->code        = 'AC_SHEET_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('SheetLockedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_LOCKED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlLockedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_DRAFTED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_DRAFTED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlDraftedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'CONTROL_VALIDATED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'control@dolismq';
				$actioncomm->code        = 'AC_CONTROL_VALIDATED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ControlValidatedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			default:
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}


		return 0;
	}
}
