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
 * \file        class/question.class.php
 * \ingroup     dolismq
 * \brief       This file is a CRUD class file for Question (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for Question
 */
class Question extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'dolismq';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'question';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'dolismq_question';

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
	 * @var string String with name of icon for question. Must be the part after the 'object_' into object_question.png
	 */
	public $picto = 'fontawesome_fa-question_fas_#d35968';

	const STATUS_DELETED   = -1;
	const STATUS_DRAFT     = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_LOCKED    = 2;
	const STATUS_ARCHIVED  = 3;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'                  => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'ref'                    => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'                => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "External reference of object"),
		'entity'                 => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'          => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'                    => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'             => array('type' => 'varchar(14)', 'label' => 'ImportKey', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 0,),
		'status'                 => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'default' =>'1', 'arrayofkeyval' => ['0' => 'Draft', '1' => 'Enabled', '2' => 'Locked']),
		'type'                   => array('type' => 'varchar(128)', 'label' => 'Type', 'enabled' => '1', 'position' => 80, 'notnull' => 1, 'visible' => 1,),
		'label'                  => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 11, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',),
		'description'            => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 3,),
		'show_photo'             => array('type' => 'boolean', 'label' => 'ShowPhoto', 'enabled' => '1', 'position' => 105, 'notnull' => 0, 'visible' => 0,),
		'authorize_answer_photo' => array('type' => 'boolean', 'label' => 'AuthorizeAnswerPhoto', 'enabled' => '1', 'position' => 106, 'notnull' => 0, 'visible' => 0,),
		'enter_comment'          => array('type' => 'boolean', 'label' => 'EnterComment', 'enabled' => '1', 'position' => 107, 'notnull' => 0, 'visible' => 0,),
		'photo_ok'               => array('type' => 'text', 'label' => 'PhotoOK', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 3,),
		'photo_ko'               => array('type' => 'text', 'label' => 'PhotoKO', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 3,),
		'fk_user_creat'          => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif'          => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => 0,),
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
	public $label;
	public $description;
	public $show_photo;
	public $authorize_answer_photo;
	public $enter_comment;
	public $photo_ok;
	public $photo_ko;
	public $fk_user_creat;
	public $fk_user_modif;

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
		global $conf;
		$refQuestionMod = new $conf->global->DOLISMQ_QUESTION_ADDON($this->db);
		$this->status = 1;
		$this->ref = $refQuestionMod->getNextValue($this);
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
		$this->status = $this::STATUS_DELETED;
		return $this->update($user, $notrigger);
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
		return $this->setStatusCommon($user, self::STATUS_LOCKED, $notrigger, 'QUESTION_LOCKED');
	}

	/**
	 *	Set archived status
	 *
	 *	@param  User $user	    Object user that modify
	 *  @param  int  $notrigger 1=Does not execute triggers, 0=Execute triggers
	 *	@return	int			    0 < if KO, >0 if OK
	 */
	public function setArchived(User $user, int $notrigger = 0): int
	{
		return $this->setStatusCommon($user, self::STATUS_ARCHIVED, $notrigger, 'QUESTION_ARCHIVED');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isErasable() {
		return $this->isLinkedToOtherObjects();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question is linked to another object
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isLinkedToOtherObjects() {

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= " WHERE fk_target = " . $this->id;
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
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $saveLastSearchValue      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $saveLastSearchValue = -1)
	{
		global $conf, $langs;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<i class="fas fa-question" style="color: #d35968;"></i> <u>' . $langs->trans("Question") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/dolismq/view/question/question_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$addSaveLastSearchValues = ($saveLastSearchValue == 1 ? 1 : 0);
			if ($saveLastSearchValue == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $addSaveLastSearchValues = 1;
			if ($addSaveLastSearchValues) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans("ShowQuestion");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart  = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		if ($withpicto) $result .= '<i class="fas fa-question" style="color: #d35968;"></i>' . ' ';
		$result .= $linkstart;
		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('questiondao'));
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
	 *  Return the status
	 *
	 *  @param  int    $status Id status
	 *  @param  int    $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string         Label of status
	 */
	public function LibStatut(int $status, int $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
			$this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
			$this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');

			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
			$this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
			$this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
		}

		$statusType = 'status' . $status;
		if ($status == self::STATUS_LOCKED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_ARCHIVED) {
			$statusType = 'status8';
		}
		if ($status == self::STATUS_DELETED) {
			$statusType = 'status9';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
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
	 * Clone an object into another one
	 *
	 * @param  User      $user    User that creates
	 * @param  int       $fromid  ID of object to clone
	 * @param  array     $options Options array
	 * @return int                New object created, <0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, int $fromid, array $options): int
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $conf;
		$error = 0;

		$refQuestionMod = new $conf->global->DOLISMQ_QUESTION_ADDON($this->db);
		require_once __DIR__ . '/../core/modules/dolismq/question/mod_question_standard.php';

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		$oldRef = $object->ref;

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = $refQuestionMod->getNextValue($object);
		}
		if (!empty($options['label'])) {
			if (property_exists($object, 'label')) {
				$object->label = $options['label'];
			}
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = 1;
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->create($user);

		if ($result > 0) {
			if (!empty($options['categories'])) {
				$cat        = new Categorie($this->db);
				$categories = $cat->containing($fromid, 'question');
				if (is_array($categories) && !empty($categories)) {
					foreach ($categories as $cat) {
						$categoryIds[] = $cat->id;
					}
					$object->fetch($result);
					$object->setCategories($categoryIds);
				}
			}
			if (!empty($options['photos'])) {
				$dirFiles = $conf->dolismq->multidir_output[$object->entity ?? 1] . '/question/';
				$oldDirFiles = $dirFiles . $oldRef;
				$newDirFiles = $dirFiles . $object->ref;

				$photoOkList = dol_dir_list($oldDirFiles . '/photo_ok', 'files');
				$photoKoList = dol_dir_list($oldDirFiles . '/photo_ko', 'files');

				$photoOkThumbsList = dol_dir_list($oldDirFiles . '/photo_ok/thumbs', 'files');
				$photoKoThumbsList = dol_dir_list($oldDirFiles . '/photo_ko/thumbs', 'files');

				$photoOkPath = $newDirFiles . '/photo_ok';
				dol_mkdir($photoOkPath);
				if (is_array($photoOkList) && !empty($photoOkList)) {
					foreach ($photoOkList as $photoOk) {
						copy($photoOk['fullname'], $photoOkPath . '/' . $photoOk['name']);
					}
				}

				$photoKoPath = $newDirFiles . '/photo_ko';
				dol_mkdir($photoKoPath);
				if (is_array($photoKoList) && !empty($photoKoList)) {
					foreach ($photoKoList as $photoKo) {
						copy($photoKo['fullname'], $photoKoPath . '/' . $photoKo['name']);
					}
				}

				$photoOkThumbsPath = $newDirFiles . '/photo_ok/thumbs';
				dol_mkdir($photoOkThumbsPath);
				if (is_array($photoOkThumbsList) && !empty($photoOkThumbsList)) {
					foreach ($photoOkThumbsList as $photoOkThumbs) {
						copy($photoOkThumbs['fullname'], $photoOkThumbsPath . '/' . $photoOkThumbs['name']);
					}
				}

				$photoKoThumbsPath = $newDirFiles . '/photo_ok/thumbs';
				dol_mkdir($photoKoThumbsPath);
				if (is_array($photoKoThumbsList) && !empty($photoKoThumbsList)) {
					foreach ($photoKoThumbsList as $photoKoThumbs) {
						copy($photoKoThumbs['fullname'], $photoKoThumbsPath . '/' . $photoKoThumbs['name']);
					}
				}
			}
		} else {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $result;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param  int[]|int $categories Category or categories IDs
	 * @return void
	 */
	public function setCategories($categories)
	{
		return parent::setCategoriesCommon($categories, 'question');
	}

	/**
	 * Check if question locked
	 *
	 * @param array $questionIds
	 * @return int
	 */
	public function checkQuestionsLocked($questionIds)
	{
		if (is_array($questionIds) && !empty($questionIds)) {
			foreach ($questionIds as $questionId) {
				$this->fetch($questionId);
				if ($this->status == 2) {
					continue;
				} else {
					return 0;
				}
			}
			return 1;
		} else {
			return 0;
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
	 *  Output html form to select a third party.
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
	 *
	 * @param string $selected   Preselected type
	 * @param string $htmlname   Name of field in form
	 * @param string $filter     Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 * @param string $showempty  Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * @param int    $showtype   Show third party type in combolist (customer, prospect or supplier)
	 * @param int    $forcecombo Force to use standard HTML select component without beautification
	 * @param array  $events     Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 * @param string $filterkey  Filter on key value
	 * @param int    $outputmode 0=HTML select string, 1=Array
	 * @param int    $limit      Limit number of answers
	 * @param string $morecss    Add more css styles to the SELECT component
	 * @param string $moreparam  Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 * @param bool   $multiple   add [] in the name of element and add 'multiple' attribut
	 * @return       string      HTML string with
	 * @throws Exception
	 */
	public function selectQuestionList($selected = '', $htmlname = 'socid', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $multiple = false, $alreadyAdded = array())
	{
		$out      = '';
		$num      = 0;
		$outarray = array();

		if ($selected === '') $selected           = array();
		elseif ( ! is_array($selected)) $selected = array($selected);

		// Clean $filter that may contains sql conditions so sql code
		if (function_exists('testSqlAndScriptInject')) {
			if (testSqlAndScriptInject($filter, 3) > 0) {
				$filter = '';
			}
		}
		// On recherche les societes
		$sql  = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "dolismq_question as s";

		$sql              .= " WHERE s.entity IN (" . getEntity($this->table_element) . ")";
		if ($filter) $sql .= " AND (" . $filter . ")";

		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this) . "::selectQuestionList", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ( ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";

			$num                  = $this->db->num_rows($resql);
			$i                    = 0;

			if ($num) {
				while ($i < $num) {
					$obj   = $this->db->fetch_object($resql);
					$label = $obj->ref . ' - ' . $obj->label;


					if (empty($outputmode)) {
						if (in_array($obj->rowid, $selected)) {
							$out .= '<option value="' . $obj->rowid . '" selected>' . $label . '</option>';
						} else {
							if (!empty($alreadyAdded)) {
								if (in_array($obj->rowid, $alreadyAdded)) {
									$out .= '<option disabled value="' . $obj->rowid . '">' . $label . '</option>';
								} else {
									$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
								}
							} else {
								$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
							}
						}
					} else {
						array_push($outarray, array('key' => $obj->rowid, 'value' => $label, 'label' => $label));
					}

					$i++;
					if (($i % 10) == 0) $out .= "\n";
				}
			}
			$out .= '</select>' . "\n";
		} else {
			dol_print_error($this->db);
		}

		$this->result = array('nbofquestions' => $num);

		if ($outputmode) return $outarray;
		return $out;
	}

	/**
	 *	Update questions position in sheet
	 *
	 *	@param	array	$idsArray			Array containing position and ids of questions in sheet
	 */
	public function updateAnswersPosition($idsArray)
	{
		$this->db->begin();

		foreach ($idsArray as $position => $answerId) {
			$sql = 'UPDATE '. MAIN_DB_PREFIX . 'dolismq_answer';
			$sql .= ' SET position =' . $position;
			$sql .= ' WHERE fk_question = ' . $this->id;
			$sql .= ' AND rowid =' . $answerId;
			$res = $this->db->query($sql);

			if (!$res) {
				$error++;
			}
		}
		if ($error) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}
	}
}
