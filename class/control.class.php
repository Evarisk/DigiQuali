<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 */


/**
 * \file        class/control.class.php
 * \ingroup     dolismq
 * \brief       This file is a CRUD class file for Control (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for Control
 */
class Control extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'dolismq';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'control';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'dolismq_control';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for control. Must be the part after the 'object_' into object_control.png
	 */
	public $picto = 'control@dolismq';

	const STATUS_DRAFT     = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_LOCKED    = 2;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'              => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'ref'                => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'            => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "External reference of object"),
		'entity'             => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'      => array('type' => 'datetime', 'label' => 'ControlDate', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 5,),
		'tms'                => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'         => array('type' => 'integer', 'label' => 'ImportKey', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 0,),
		'status'             => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => '0'),
		'type'               => array('type' => 'varchar(128)', 'label' => 'Type', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 0,),
		'verdict'            => array('type' => 'smallint', 'label' => 'Verdict', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 5, 'index' => 1, 'arrayofkeyval' => array('0' => '', '1' => 'OK', '2' => 'KO'),),
		'fk_user_creat'      => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif'      => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => 0,),
		'fk_sheet'           => array('type' => 'integer:Sheet:custom/dolismq/class/sheet.class.php', 'label' => 'FKSheet', 'enabled' => '1', 'position' => 13, 'notnull' => 1, 'visible' => 2,),
		'fk_user_controller' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'FKUserController', 'enabled' => '1', 'position' => 14, 'notnull' => 1, 'visible' => 2, 'foreignkey' => 'user.rowid'),
		'fk_product'         => array('type' => 'integer:Product:product/class/product.class.php', 'label' => 'Product', 'enabled' => '1', 'position' => 11, 'notnull' => 1, 'visible' => 2, 'foreignkey' => 'product.rowid'),
		'fk_lot'             => array('type' => 'integer:Productlot:product/stock/class/productlot.class.php', 'label' => 'Batch', 'enabled' => '1', 'position' => 12, 'notnull' => 0, 'visible' => 2, 'foreignkey' => 'productlot.rowid'),
		'fk_project'         => array('type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Projet', 'enabled' => '1', 'position' => 12, 'notnull' => 0, 'visible' => 2, 'foreignkey' => 'project.rowid'),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $type;
	public $verdict;
	public $label;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_sheet;
	public $fk_user_controller;
	public $fk_product;
	public $fk_lot;
	public $fk_project;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if ( ! empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && ! empty($object->table_element_line)) $object->fetchLines();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) $object->ref     = empty($this->fields['ref']['default']) ? "Copy_Of_" . $object->ref : $this->fields['ref']['default'];
		if (property_exists($object, 'label')) $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
		if (property_exists($object, 'status')) { $object->status = self::STATUS_DRAFT; }
		if (property_exists($object, 'date_creation')) { $object->date_creation = dol_now(); }
		if (property_exists($object, 'date_modification')) { $object->date_modification = null; }
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if ( ! empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		if ( ! $error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if ( ! $error) {
			// copy external contacts if same company
			if (property_exists($this, 'socid') && $this->socid == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0)
					$error++;
			}
		}

		unset($object->context['createfromclone']);

		// End
		if ( ! $error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql                                                                              = 'SELECT ';
		$sql                                                                             .= $this->getFieldList();
		$sql                                                                             .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql                                                                        .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if ( ! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if ( ! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->control->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->control->control_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if ( ! $error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if ( ! empty($num)) {
			// Validate
			$sql                                                  = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
			$sql                                                 .= " SET ref = '" . $this->db->escape($num) . "',";
			$sql                                                 .= " status = " . self::STATUS_VALIDATED;
			if ( ! empty($this->fields['date_validation'])) $sql .= ", date_validation = '" . $this->db->idate($now) . "'";
			if ( ! empty($this->fields['fk_user_valid'])) $sql   .= ", fk_user_valid = " . $user->id;
			$sql                                                 .= " WHERE rowid = " . $this->id;

			dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ( ! $resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if ( ! $error && ! $notrigger) {
				// Call trigger
				$result = $this->call_trigger('AUDIT_VALIDATE', $user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}

		if ( ! $error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql   = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'control/" . $this->db->escape($this->newref) . "'";
				$sql  .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'control/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
				$resql = $this->db->query($sql);
				if ( ! $resql) { $error++; $this->error = $this->db->lasterror(); }

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref    = dol_sanitizeFileName($this->ref);
				$newref    = dol_sanitizeFileName($num);
				$dirsource = $conf->dolismq->dir_output . '/control/' . $oldref;
				$dirdest   = $conf->dolismq->dir_output . '/control/' . $newref;
				if ( ! $error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->dolismq->dir_output . '/control/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest   = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
							$dirsource = $fileentry['path'] . '/' . $dirsource;
							$dirdest   = $fileentry['path'] . '/' . $dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if ( ! $error) {
			$this->ref    = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if ( ! $error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CONTROL_DRAFTED');
	}

	/**
	 *	Set validate status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setValidated($user, $notrigger = 0)
	{
		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CONTROL_VALIDATED');
	}

	/**
	 *	Set lock status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setLocked($user, $notrigger = 0)
	{
		return $this->setStatusCommon($user, self::STATUS_LOCKED, $notrigger, 'CONTROL_LOCKED');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->dolismq_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'AUDIT_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolismq->dolismq_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'AUDIT_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("Control") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/dolismq/view/control/control_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans("ShowControl");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart  = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir           = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray            = dol_dir_list($upload_dir, "files");
				$filename             = $filearray[0]['name'];
				if ( ! empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('controldao'));
		$parameters               = array('id' => $this->id, 'getnomurl' => $result);
		$reshook                  = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result             .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("dolismq@dolismq");
			$this->labelStatus[self::STATUS_DRAFT]          = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_VALIDATED]       = $langs->trans('ValidatedControl');
			$this->labelStatus[self::STATUS_LOCKED]       = $langs->trans('Locked');
			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED]     = $langs->trans('ValidatedControl');
			$this->labelStatusShort[self::STATUS_LOCKED]     = $langs->trans('Locked');
		}

		$statusType = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) $statusType = 'status4';
		if ($status == self::STATUS_LOCKED) $statusType = 'status4';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql    = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql   .= ' fk_user_creat, fk_user_modif';
		$sql   .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql   .= ' WHERE t.rowid = ' . $id;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj      = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new ControlLine($this->db);
		$result     = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_control = ' . $this->id));

		if (is_numeric($result)) {
			$this->error  = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("dolismq@dolismq");

		if (empty($conf->global->DOLISMQ_AUDIT_ADDON)) {
			$conf->global->DOLISMQ_AUDIT_ADDON = 'mod_control_standard';
		}

		if ( ! empty($conf->global->DOLISMQ_AUDIT_ADDON)) {
			$mybool = false;

			$file      = $conf->global->DOLISMQ_AUDIT_ADDON . ".php";
			$classname = $conf->global->DOLISMQ_AUDIT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/dolismq/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj    = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result               = 0;
		$includedocgeneration = 1;

		$langs->load("dolismq@dolismq");

		if ( ! dol_strlen($modele)) {
			$modele = 'standard_control';

			if ( ! empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif ( ! empty($conf->global->AUDIT_ADDON_PDF)) {
				$modele = $conf->global->AUDIT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/dolismq/controldocument/";

		if ($includedocgeneration && ! empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams['object']);
		}

		return $result;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error        = 0;
		$this->output = '';
		$this->error  = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}


	/**
	 *	Fetch array of objects linked to current object (object of enabled modules only). Links are loaded into
	 *		this->linkedObjectsIds array +
	 *		this->linkedObjects array if $loadalsoobjects = 1
	 *  Possible usage for parameters:
	 *  - all parameters empty -> we look all link to current object (current object can be source or target)
	 *  - source id+type -> will get target list linked to source
	 *  - target id+type -> will get source list linked to target
	 *  - source id+type + target type -> will get target list of the type
	 *  - target id+type + target source -> will get source list of the type
	 *
	 *	@param	int		$sourceid			Object source id (if not defined, id of object)
	 *	@param  string	$sourcetype			Object source type (if not defined, element name of object)
	 *	@param  int		$targetid			Object target id (if not defined, id of object)
	 *	@param  string	$targettype			Object target type (if not defined, elemennt name of object)
	 *	@param  string	$clause				'OR' or 'AND' clause used when both source id and target id are provided
	 *  @param  int		$alsosametype		0=Return only links to object that differs from source type. 1=Include also link to objects of same type.
	 *  @param  string	$orderby			SQL 'ORDER BY' clause
	 *  @param	int		$loadalsoobjects	Load also array this->linkedObjects (Use 0 to increase performances)
	 *	@return int							<0 if KO, >0 if OK
	 *  @see	add_object_linked(), updateObjectLinked(), deleteObjectLinked()
	 */
	public function fetchQuestionsLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
	{
		global $conf;

		$this->linkedObjectsIds = array();
		$this->linkedObjects = array();

		$justsource = false;
		$justtarget = false;
		$withtargettype = false;
		$withsourcetype = false;

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid)) {
			$justsource = true; // the source (id and type) is a search criteria
			if (!empty($targettype)) {
				$withtargettype = true;
			}
		}
		if (!empty($targetid) && !empty($targettype) && empty($sourceid)) {
			$justtarget = true; // the target (id and type) is a search criteria
			if (!empty($sourcetype)) {
				$withsourcetype = true;
			}
		}

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$targetid = (!empty($targetid) ? $targetid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
		$targettype = (!empty($targettype) ? $targettype : $this->element);

		/*if (empty($sourceid) && empty($targetid))
		 {
		 dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERR);
		 return -1;
		 }*/

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= " WHERE ";
		if ($justsource || $justtarget) {
			if ($justsource) {
				$sql .= "fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."'";
				if ($withtargettype) {
					$sql .= " AND targettype = '".$this->db->escape($targettype)."'";
				}
			} elseif ($justtarget) {
				$sql .= "fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape($targettype)."'";
				if ($withsourcetype) {
					$sql .= " AND sourcetype = '".$this->db->escape($sourcetype)."'";
				}
			}
		} else {
			$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."')";
			$sql .= " ".$clause." (fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape($targettype)."')";
		}
		$sql .= ' ORDER BY '.$orderby;

		dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($justsource || $justtarget) {
					if ($justsource) {
						$this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
					} elseif ($justtarget) {
						$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
					}
				} else {
					if ($obj->fk_source == $sourceid && $obj->sourcetype == $sourcetype) {
						$this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
					}
					if ($obj->fk_target == $targetid && $obj->targettype == $targettype) {
						$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
					}
				}

				$i++;
			}

			if (!empty($this->linkedObjectsIds)) {
				$tmparray = $this->linkedObjectsIds;
				foreach ($tmparray as $objecttype => $objectids) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
					// Parse element/subelement (ex: project_task, cabinetmed_consultation, ...)
					$module = $element = $subelement = $objecttype;
					$regs = array();
					if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier'
						&& preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
						$module = $element = $regs[1];

						$subelement = $regs[2];
					}
					// Here $module, $classfile and $classname are set
					if ((($element != $this->element) || $alsosametype)) {
						if ($loadalsoobjects) {
							dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
							//print '/'.$classpath.'/'.$classfile.'.class.php '.class_exists($classname);
							if (class_exists($classname)) {
								foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
									$object = new $classname($this->db);
									$ret = $object->fetch($objectid);
									if ($ret >= 0) {
										$this->linkedObjects[$objecttype][$i] = $object;
									}
								}
							}
						}
					} else {
						unset($this->linkedObjectsIds[$objecttype]);
					}
				}
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

}
class ControlLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'controldet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'dolismq_controldet';

	public $ref = '';

	public $date_creation = '';

	public $comment = '';

	public $answer = '';

	public $fk_control = '';

	public $fk_question = '';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'             => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'               => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'           => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'            => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'     => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'               => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'status'            => array('type' => 'status', 'label' => 'Status', 'enabled' => '1', 'position' => 55, 'notnull' => 0, 'visible' => 0,),
		'comment'           => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => -1,),
		'fk_question'       => array('type' => 'integer', 'label' => 'FkQuestion', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => 0,),
		'fk_control'        => array('type' => 'integer', 'label' => 'FkControl', 'enabled' => '1', 'position' => 100, 'notnull' => 1, 'visible' => 0,),
	);

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;
	}

	/**
	 *	Load prevention plan line from database
	 *
	 *	@param	int		$rowid      id of invoice line to get
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		global $db;


		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE t.rowid = ' . $rowid;
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);
		if ($result) {
			$objp = $db->fetch_object($result);

			$this->id                = $objp->rowid;
			$this->ref               = $objp->ref;
			$this->date_creation     = $objp->date_creation;
			$this->status       = $objp->status;
			$this->answer          = $objp->answer;
			$this->comment          = $objp->comment;
			$this->fk_question = $objp->fk_question;
			$this->fk_control = $objp->fk_control;

			$db->free($result);

			return $this->id;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Load preventionplan line line from database
	 *
	 * @param int $parent_id
	 * @param int $limit
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($limit = 0)
	{
		global $db;
		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';


		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id                = $obj->rowid;
				$record->ref               = $obj->ref;
				$record->date_creation     = $obj->date_creation;
				$record->status       = $obj->status;
				$record->comment          = $obj->comment;
				$record->fk_question = $obj->fk_question;
				$record->fk_control = $obj->fk_control;

				$records[$record->id] = $record;

				$i++;
			}

			$db->free($result);

			return $records;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}


	/**
	 *    Load preventionplan line line from database
	 *
	 * @param int $control_id
	 * @param int $question_id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchFromParentWithQuestion($control_id, $question_id, $limit = 0)
	{
		global $db;
		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
		$sql .= ' AND fk_control = ' . $control_id .' AND fk_question ='. $question_id;


		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id                = $obj->rowid;
				$record->ref               = $obj->ref;
				$record->date_creation     = $obj->date_creation;
				$record->status       = $obj->status;
				$record->answer          = $obj->answer;
				$record->comment          = $obj->comment;
				$record->fk_question = $obj->fk_question;
				$record->fk_control = $obj->fk_control;

				$records[$record->id] = $record;

				$i++;
			}

			$db->free($result);

			return $records;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}

	}

	/**
	 *    Insert line into database
	 *
	 * @param User $user
	 * @param bool $notrigger 1 no triggers
	 * @return        int                                         <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function insert(User $user, $notrigger = false)
	{
		global $db, $user;

		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();
		$now = dol_now();

		// Insertion dans base de la ligne
		$sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'dolismq_controldet';
		$sql .= ' ( ref, entity, status, date_creation, answer, comment, fk_question, fk_control, fk_user_creat';
		$sql .= ')';
		$sql .= " VALUES (";
		$sql .= "'" . $db->escape($this->ref) . "'" . ", ";
		$sql .= $this->entity . ", ";
		$sql .= 1 . ", ";
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ", ";
		$sql .= "'" . $db->escape($this->answer) . "'" . ", ";
		$sql .= "'" . $db->escape($this->comment) . "'" . ", ";
		$sql .= $this->fk_question . ", ";
		$sql .= $this->fk_control . ", ";
		$sql .= $user->id;

		$sql .= ')';

		dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$this->id    = $db->last_insert_id(MAIN_DB_PREFIX . 'controldet');
			$this->rowid = $this->id; // For backward compatibility

			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)) . '_CREATE', $user);
				// End call triggers
			}
			return $this->id;
		} else {
			$this->error = $db->lasterror();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Update line into database
	 *
	 * @param User $user User object
	 * @param int $notrigger Disable triggers
	 * @return        int                    <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function update(User $user, $notrigger = false)
	{
		global $user, $db;

		$error = 0;

		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();

		// Mise a jour ligne en base
		$sql  = "UPDATE " . MAIN_DB_PREFIX . "dolismq_controldet SET";

		$sql .= " ref='" . $db->escape($this->ref) . "',";
		$sql .= " status='" . $db->escape($this->status) . "',";
		$sql .= " answer=" . $db->escape($this->answer) . ",";
		$sql .= " comment=" . '"' . $db->escape($this->comment) . '"' .",";
		$sql .= " fk_question=" . $db->escape($this->fk_question). ",";
		$sql .= " fk_control=" . $db->escape($this->fk_control);

		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);

		$resql = $db->query($sql);

		if ($resql) {
			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)) . '_MODIFY', $user);
				// End call triggers
			}
			return $this->id;
		} else {
			$this->error = $db->error();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Delete line in database
	 *
	 * @return        int                   <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function delete(User $user, $notrigger = false)
	{
		global $user, $db;

		$db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "dolismq_controldet WHERE rowid = " . $this->id;
		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		if ($db->query($sql)) {
			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call trigger
				$this->call_trigger(strtoupper(get_class($this)) . '_DELETE', $user);
				// End call triggers
			}
			return 1;
		} else {
			$this->error = $db->error() . " sql=" . $sql;
			$db->rollback();
			return -1;
		}
	}
}

