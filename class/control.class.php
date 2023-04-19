<?php
/* Copyright (C) 2022 EVARISK <technique@evarisk.com>
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
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for control. Must be the part after the 'object_' into object_control.png
	 */
	public $picto = 'fontawesome_fa-tasks_fas_#d35968';

	public const STATUS_DRAFT     = 0;
	public const STATUS_VALIDATED = 1;
	public const STATUS_LOCKED    = 2;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = [
		'rowid'              => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => 'Id'],
		'ref'                => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => 'Reference of object'],
		'ref_ext'            => ['type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0],
		'entity'             => ['type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0],
		'date_creation'      => ['type' => 'datetime', 'label' => 'ControlDate', 'enabled' => '1', 'position' => 40, 'positioncard' => 10, 'notnull' => 1, 'visible' => 5],
		'tms'                => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0],
		'import_key'         => ['type' => 'varchar(14)', 'label' => 'ImportKey', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 0],
		'status'             => ['type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => '0', 'arrayofkeyval' => ['0' => 'Draft', '1' => 'Validated', '2' => 'Locked']],
		'note_public'        => ['type' => 'html', 'label' => 'PublicNote', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 0],
		'note_private'       => ['type' => 'html', 'label' => 'PrivateNote', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 0],
		'type'               => ['type' => 'varchar(128)', 'label' => 'Type', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 0],
		'verdict'            => ['type' => 'smallint', 'label' => 'Verdict', 'enabled' => '1', 'position' => 110,'positioncard' => 20, 'notnull' => 0, 'visible' => 5, 'index' => 1, 'arrayofkeyval' => ['0' => 'All', '1' => 'OK', '2' => 'KO', '3' => 'NoVerdict']],
		'fk_user_creat'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
		'fk_user_modif'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => 0],
		'fk_sheet'           => ['type' => 'integer:Sheet:dolismq/class/sheet.class.php', 'label' => 'SheetLinked', 'enabled' => '1', 'position' => 23, 'notnull' => 1, 'visible' => 5, 'css' => 'maxwidth500 widthcentpercentminusxx'],
		'fk_user_controller' => ['type' => 'integer:User:user/class/user.class.php:1', 'label' => 'FKUserController','positioncard' => 1, 'enabled' => '1', 'position' => 24, 'notnull' => 1, 'visible' => 3, 'css' => 'maxwidth500 widthcentpercentminusxx', 'picto' => 'user', 'foreignkey' => 'user.rowid'],
		'projectid'         => ['type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project','positioncard' => 2, 'enabled' => '1', 'position' => 25, 'notnull' => 0, 'visible' => 3, 'css' => 'maxwidth500 widthcentpercentminusxx', 'picto' => 'project', 'foreignkey' => 'projet.rowid']
	];

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
				if (is_array($val['arrayofkeyval']) && !empty($val['arrayofkeyval'])) {
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
				$record->fetchObjectLinked('', 'product', '', 'dolismq_control');

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

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CONTROL_UNVALIDATED');
	}

	/**
	 *	Set validate status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 *  @throws Exception
	 */
	public function setValidated($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		// Define new ref
		if ((preg_match('/^\(?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happen, but when it occurs, the test save life
			$newref = $this->getNextNumRef();
		} else {
			$newref = $this->ref;
		}

		if (!empty($newref)) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($newref)."',";
			$sql .= " status = ".self::STATUS_VALIDATED;
			$sql .= " WHERE rowid = ".($this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CONTROL_VALIDATED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			// Rename directory if dir was a temporary ref
			if (preg_match('/^\(?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'control/".$this->db->escape($newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'control/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $newref = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($newref);

				$dirsource = $conf->dolismq->dir_output.'/control/'.$oldref;
				$dirdest = $conf->dolismq->dir_output.'/control/'.$newref;

				if (is_dir($dirsource)) {
					rename($dirsource, $dirdest);
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $newref;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a control can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isErasable() {
		return $this->isLinkedToOtherObjects();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a control is linked to another object
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isLinkedToOtherObjects() {

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= ' WHERE fk_target = ' . $this->id;
		$sql .= " AND targettype = '" . $this->table_element . "'";

		$resql = $this->db->query($sql);

		if ($resql) {
			$nbObjectsLinked = 0;
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$nbObjectsLinked++;
				$i++;
			}
			if ($nbObjectsLinked > 0) {
				return -1;
			} else {
				return 1;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @param $options
	 * @return    mixed                New object created, <0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $conf, $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);
		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && ! empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Create clone
		$object->fetchObjectLinked('','',$object->id, 'dolismq_' . $object->element);
		$object->context['createfromclone'] = 'createfromclone';
		$object->ref = '';
		$object->status = 0;
		$object->verdict = null;
		$object->note_public = null;
		$objectid = $object->create($user);



		//add categories
		$cat = new Categorie($this->db);
		$categories = $cat->containing($fromid, 'control');

		if (is_array($categories) && !empty($categories)) {
			foreach($categories as $cat) {
				$categoryIds[] = $cat->id;
			}
			if ($objectid > 0) {
				$object->fetch($objectid);
				$object->setCategories($categoryIds);
			}
		}

		//add objects linked
		if (is_array($object->linkedObjects) && !empty($object->linkedObjects)) {
			if (!empty($object->linkedObjects['project'])) {
				foreach ($object->linkedObjects['project'] as $project) {
					$object->add_object_linked($project->element, $project->id);
				}
			}
			if (!empty($object->linkedObjects['project_task'])) {
				foreach ($object->linkedObjects['project_task'] as $project_task) {
					$object->add_object_linked($project_task->element, $project_task->id);
				}
			}
			if (!empty($object->linkedObjects['product'])) {
				foreach ($object->linkedObjects['product'] as $product) {
					$object->add_object_linked($product->element, $product->id);
				}
			}
			if (!empty($object->linkedObjects['productbatch'])) {
				foreach ($object->linkedObjects['productbatch'] as $productbatch) {
					$object->add_object_linked($productbatch->element, $productbatch->id);
				}
			}
			if (!empty($object->linkedObjects['societe'])) {
				foreach ($object->linkedObjects['societe'] as $societe) {
					$object->add_object_linked($societe->element, $societe->id);
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if ( ! $error) {
			$this->db->commit();
			return $objectid;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $saveLastSearchValue       -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $saveLastSearchValue = -1)
	{
		global $conf, $langs;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<i class="fas fa-tasks" style="color: #d35968;"></i> <u>'.$langs->trans('Control').'</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/dolismq/view/control/control_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$addSaveLastSearchValues = ($saveLastSearchValue == 1 ? 1 : 0);
			if ($saveLastSearchValue == -1 && preg_match('/list\.php/', $_SERVER['PHP_SELF'])) $addSaveLastSearchValues = 1;
			if ($addSaveLastSearchValues) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans('ShowControl');
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart  = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		if ($withpicto) $result .= '<i class="fas fa-tasks" style="color: #d35968;"></i>' . ' ';
		$result .= $linkstart;
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

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibVerdict($mode = 0)
	{
		return $this->libVerdict($this->verdict, $mode);
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
			$this->labelStatus[self::STATUS_DRAFT]          = $langs->trans('StatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED]      = $langs->trans('Validated');
			$this->labelStatus[self::STATUS_LOCKED]         = $langs->trans('Locked');
			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->trans('StatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Validated');
			$this->labelStatusShort[self::STATUS_LOCKED]    = $langs->trans('Locked');
		}

		$statusType = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) $statusType = 'status4';
		if ($status == self::STATUS_LOCKED) $statusType = 'status6';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$verdict        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function libVerdict($verdict, $mode = 0)
	{
		global $langs;

		$this->labelStatus[0] = $langs->trans('NA');
		$this->labelStatus[1] = $langs->trans('OK');
		$this->labelStatus[2] = $langs->trans('KO');

		$verdictType = 'status' . $verdict;
		if ($verdict == 0) $verdictType = 'status6';
		if ($verdict == 1) $verdictType = 'status4';
		if ($verdict == 2) $verdictType = 'status8';

		return dolGetStatus($this->labelStatus[$verdict], $this->labelStatusShort[$verdict], '', $verdictType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int   $id ID of object
	 *	@return	void
	 */
	public function info(int $id): void
	{
		$sql = 'SELECT t.rowid, t.date_creation as datec, t.tms as datem,';
		$sql .= ' t.fk_user_creat, t.fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = ' . $id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
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
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non-existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param  int[]|int $categories Category or categories IDs
	 * @return float|int
	 */
	public function setCategories($categories)
	{
		return parent::setCategoriesCommon($categories, 'control');
	}

	/**
	 * Returns the reference to the following non-used object depending on the active numbering module.
	 *
	 *  @return string Object free reference
	 */
	public function getNextNumRef(): string
	{
		global $langs, $conf;
		$langs->load("dolismq@dolismq");

		if (empty($conf->global->DOLISMQ_CONTROL_ADDON)) {
			$conf->global->DOLISMQ_CONTROL_ADDON = 'mod_control_standard';
		}

		if (!empty($conf->global->DOLISMQ_CONTROL_ADDON)) {
			$mybool = false;

			$file = $conf->global->DOLISMQ_CONTROL_ADDON.".php";
			$classname = $conf->global->DOLISMQ_CONTROL_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/dolismq/control/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

    /**
     * Load dashboard info.
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        $getNbControlsTagsByVerdict = $this->getNbControlsTagsByVerdict();
        $getNbControlsByVerdict     = $this->getNbControlsByVerdict();
        $getNbControlsByMonth       = $this->getNbControlsByMonth();

        $array['graphs'] = [$getNbControlsTagsByVerdict, $getNbControlsByVerdict, $getNbControlsByMonth];

        return $array;
    }

    /**
     * Get controls by verdict.
     *
     * @return array     Graph datas (label/color/type/title/data etc..).
     * @throws Exception
     */
    public function getNbControlsByVerdict(): array
    {
        global $langs;

        // Graph Title parameters.
        $array['title'] = $langs->transnoentities('ControlsRepartition');
        $array['picto'] = $this->picto;

        // Graph parameters.
        $array['width']   = 800;
        $array['height']  = 400;
        $array['type']    = 'pie';
        $array['dataset'] = 1;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('NoVerdict'),
                'color' => '#999999'
            ],
            1 => [
                'label' => $langs->transnoentities('OK'),
                'color' => '#47e58e'
            ],
            2 => [
                'label' => $langs->transnoentities('KO'),
                'color' => '#e05353'
            ],
        ];

        $arrayNbControlByVerdict = [];
        $controls = $this->fetchAll('', '', 0, 0, ['customsql' => 'status >= 1']);
        if (is_array($controls) && !empty($controls)) {
            foreach ($controls as $control) {
                if (empty($control->verdict)) {
                    $arrayNbControlByVerdict[0]++;
                } else {
                    $arrayNbControlByVerdict[$control->verdict]++;
                }
            }
        }

        $array['data'] = $arrayNbControlByVerdict;

        return $array;
    }

    /**
     * Get controls with tags by verdict.
     *
     * @return array     Graph datas (label/color/type/title/data etc..).
     * @throws Exception
     */
    public function getNbControlsTagsByVerdict(): array
    {
        global $db, $langs;

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $category = new Categorie($db);

        // Graph Title parameters.
        $array['title'] = $langs->transnoentities('ControlsTagsRepartition');
        $array['picto'] = $this->picto;

        // Graph parameters.
        $array['width']   = 800;
        $array['height']  = 400;
        $array['type']    = 'bar';
        $array['dataset'] = 3;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('NoVerdict'),
                'color' => '#999999'
            ],
            1 => [
                'label' => $langs->transnoentities('OK'),
                'color' => '#47e58e'
            ],
            2 => [
                'label' => $langs->transnoentities('KO'),
                'color' => '#e05353'
            ]
        ];

        $categories = $category->get_all_categories('control');
        if (is_array($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $arrayNbControlByVerdict = [];
                $controls = $category->getObjectsInCateg('control');
                if (is_array($controls) && !empty($controls)) {
                    foreach ($controls as $control) {
                        if (empty($control->verdict)) {
                            $arrayNbControlByVerdict[0]++;
                        } else {
                            $arrayNbControlByVerdict[$control->verdict]++;
                        }
                    }
                    $array['data'][] = [$category->label, $arrayNbControlByVerdict[0],  $arrayNbControlByVerdict[1], $arrayNbControlByVerdict[2]];
                }
            }
        }

        return $array;
    }

    /**
     * Get controls by month.
     *
     * @return array     Graph datas (label/color/type/title/data etc..).
     * @throws Exception
     */
    public function getNbControlsByMonth(): array
    {
        global $conf, $langs;

        $startMonth  = $conf->global->SOCIETE_FISCAL_MONTH_START;
        $currentYear = date('Y', dol_now());
        $years       = [0 => $currentYear - 2, 1 => $currentYear - 1, 2 => $currentYear];

        // Graph Title parameters.
        $array['title'] = $langs->transnoentities('ControlsByFiscalYear');
        $array['picto'] = $this->picto;

        // Graph parameters.
        $array['width']   = 800;
        $array['height']  = 400;
        $array['type']    = 'bars';
        $array['dataset'] = 3;

        $array['labels'] = [
            0 => [
                'label' => $langs->trans("$years[0]"),
                'color' => '#9567AA'
            ],
            1 => [
                'label' => $langs->trans("$years[1]"),
                'color' => '#4F9EBE'
            ],
            2 => [
                'label' => $langs->trans("$years[2]"),
                'color' => '#FAC461'
            ]
        ];

        $arrayNbControls = [];
        for ($i = 1; $i < 13; $i++) {
            foreach ($years as $key => $year) {
                $controls = $this->fetchAll('', '', 0, 0, ['customsql' => 'MONTH (t.date_creation) = ' . $i . ' AND YEAR (t.date_creation) = ' . $year]);
                if (is_array($controls) && !empty($controls)) {
                    $arrayNbControls[$key][$i] = count($controls);
                }
            }

            $month    = $langs->transnoentitiesnoconv('MonthShort'.sprintf('%02d', $i));
            $arrayKey = $i - $startMonth;
            $arrayKey = $arrayKey >= 0 ? $arrayKey : $arrayKey + 12;
            $array['data'][$arrayKey] = [$month, $arrayNbControls[0][$i], $arrayNbControls[1][$i], $arrayNbControls[2][$i]];
        }
        ksort($array['data']);

        return $array;
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

	public $answer_photo = '';

	public $fk_control = '';

	public $fk_question = '';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'             => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => 'Id'),
		'ref'               => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => 'Reference of object'),
		'ref_ext'           => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'            => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'     => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'               => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'status'            => array('type' => 'status', 'label' => 'Status', 'enabled' => '1', 'position' => 55, 'notnull' => 0, 'visible' => 0,),
		'answer'            => array('type' => 'text', 'label' => 'Answer', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => 0,),
		'answer_photo'      => array('type' => 'text', 'label' => 'AnswerPhoto', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => 0,),
		'comment'           => array('type' => 'text', 'label' => 'Comment', 'enabled' => '1', 'position' => 80, 'notnull' => -1, 'visible' => 0,),
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
		global $conf;

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

		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.answer_photo, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE t.rowid = ' . $rowid;
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);
		if ($result) {
			$objp = $db->fetch_object($result);

			$this->id            = $objp->rowid;
			$this->ref           = $objp->ref;
			$this->date_creation = $objp->date_creation;
			$this->status        = $objp->status;
			$this->answer        = $objp->answer;
			$this->answer_photo  = $objp->answer_photo;
			$this->comment       = $objp->comment;
			$this->fk_question   = $objp->fk_question;
			$this->fk_control    = $objp->fk_control;

			$db->free($result);

			return $this->id;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Load control line from database
	 *
	 * @param int $parent_id
	 * @param int $limit
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $db;
		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.answser_photo, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id            = $obj->rowid;
				$record->ref           = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->status        = $obj->status;
				$record->answer        = $obj->answer;
				$record->answer_photo  = $obj->answer_photo;
				$record->comment       = $obj->comment;
				$record->fk_question   = $obj->fk_question;
				$record->fk_control    = $obj->fk_control;

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
	 *    Load control line from database and from parent
	 *
	 * @param int $parent_id
	 * @param int $limit
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchFromParent($control_id, $limit = 0)
	{
		global $db;
		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.answer_photo, t.comment, t.fk_question, t.fk_control ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet as t';
		$sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
		$sql .= ' AND fk_control = ' . $control_id;

		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id            = $obj->rowid;
				$record->ref           = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->status        = $obj->status;
				$record->answer        = $obj->answer;
				$record->answer_photo  = $obj->answer_photo;
				$record->comment       = $obj->comment;
				$record->fk_question   = $obj->fk_question;
				$record->fk_control    = $obj->fk_control;

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
	 *    Load control line from database form parent with question
	 *
	 * @param int $control_id
	 * @param int $question_id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchFromParentWithQuestion($control_id, $question_id, $limit = 0)
	{
		global $db;
		$sql  = 'SELECT  t.rowid, t.ref, t.date_creation, t.status, t.answer, t.answer_photo, t.comment, t.fk_question, t.fk_control ';
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

				$record->id            = $obj->rowid;
				$record->ref           = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->status        = $obj->status;
				$record->answer        = $obj->answer;
				$record->answer_photo  = $obj->answer_photo;
				$record->comment       = $obj->comment;
				$record->fk_question   = $obj->fk_question;
				$record->fk_control    = $obj->fk_control;

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
		$sql .= ' ( ref, entity, status, date_creation, answer, answer_photo, comment, fk_question, fk_control, fk_user_creat';
		$sql .= ')';
		$sql .= ' VALUES (';
		$sql .= "'" . $db->escape($this->ref) . "'" . ', ';
		$sql .= $this->entity . ', ';
		$sql .= 1 . ', ';
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ', ';
		$sql .= "'" . $db->escape($this->answer) . "'" . ', ';
		$sql .= "'" . $db->escape($this->answer_photo) . "'" . ', ';
		$sql .= "'" . $db->escape($this->comment) . "'" . ', ';
		$sql .= $this->fk_question . ', ';
		$sql .= $this->fk_control . ', ';
		$sql .= $user->id;

		$sql .= ')';

		dol_syslog(get_class($this) . '::insert', LOG_DEBUG);
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
		$sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'dolismq_controldet SET';

		$sql .= " ref='" . $db->escape($this->ref) . "',";
		$sql .= " status='" . $db->escape($this->status) . "',";
		$sql .= " answer='" . $db->escape($this->answer) . "',";
		$sql .= ' answer_photo=' . '"' . $db->escape($this->answer_photo) . '"' . ',';
		$sql .= ' comment=' . '"' . $db->escape($this->comment) . '"' . ',';
		$sql .= ' fk_question=' . $db->escape($this->fk_question). ',';
		$sql .= ' fk_control=' . $db->escape($this->fk_control);

		$sql .= ' WHERE rowid = ' . $this->id;

		dol_syslog(get_class($this) . '::update', LOG_DEBUG);

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

		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'dolismq_controldet WHERE rowid = ' . $this->id;
		dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
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
			$this->error = $db->error() . ' sql=' . $sql;
			$db->rollback();
			return -1;
		}
	}
}

