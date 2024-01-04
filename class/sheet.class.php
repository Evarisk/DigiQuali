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
 */

/**
 * \file    class/sheet.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Sheet (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Sheet.
 */
class Sheet extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object.
     */
    public $element = 'sheet';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiquali_sheet';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for sheet. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'sheet@digiquali' if picto is file 'img/object_sheet.png'.
     */
    public string $picto = 'fontawesome_fa-list_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = [
        'rowid'               => ['type' => 'integer',      'label' => 'TechnicalID',        'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                 => ['type' => 'varchar(128)', 'label' => 'Ref',                'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'             => ['type' => 'varchar(128)', 'label' => 'RefExt',             'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'              => ['type' => 'integer',      'label' => 'Entity',             'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'       => ['type' => 'datetime',     'label' => 'DateCreation',       'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                 => ['type' => 'timestamp',    'label' => 'DateModification',   'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0],
        'import_key'          => ['type' => 'varchar(14)',  'label' => 'ImportId',           'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'              => ['type' => 'smallint',     'label' => 'Status',             'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1, 'index' => 1, 'default' =>1, 'arrayofkeyval' => ['specialCase' => 'InProgressAndLocked', 1 => 'InProgress', 2 => 'Locked', 3 => 'Archived'], 'css' => 'minwidth200'],
        'type'                => ['type' => 'varchar(128)', 'label' => 'Type',               'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0,],
        'label'               => ['type' => 'varchar(255)', 'label' => 'Label',              'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200'],
        'description'         => ['type' => 'html',         'label' => 'Description',        'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200'],
        'element_linked'      => ['type' => 'text',         'label' => 'ElementLinked',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'mandatory_questions' => ['type' => 'text',         'label' => 'MandatoryQuestions', 'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 0],
        'fk_user_creat'       => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'       => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid']
    ];

    /**
     * @var int ID.
     */
    public int $rowid;

    /**
     * @var string Ref.
     */
    public $ref;

    /**
     * @var string Ref ext.
     */
    public $ref_ext;

    /**
     * @var int Entity.
     */
    public $entity;

    /**
     * @var int|string Creation date.
     */
    public $date_creation;

    /**
     * @var int|string Timestamp.
     */
    public $tms;

    /**
     * @var string Import key.
     */
    public $import_key;

    /**
     * @var int Status.
     */
    public $status;

    /**
     * @var string|null Type.
     */
    public ?string $type = '';

    /**
     * @var string Label.
     */
    public string $label;

    /**
     * @var string|null Description.
     */
    public ?string $description;

    /**
     * @var string Element linked json.
     */
    public string $element_linked = '';

    /**
     * @var string Mandatory questions.
     */
    public ?string $mandatory_questions = '{}';

    /**
     * @var int User ID.
     */
    public int $fk_user_creat;

    /**
     * @var int|null User ID.
     */
    public ?int $fk_user_modif;

    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database.
     *
     * @param  User $user      User that creates.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
    public function create(User $user, bool $notrigger = false): int
    {
        $this->ref                 = $this->getNextNumRef();
        $this->status              = $this->status ?: 1;
		$this->mandatory_questions = isset($this->mandatory_questions) ? $this->mandatory_questions : '{}';

        return parent::create($user, $notrigger);
    }

    /**
     * Return the status.
     *
     * @param  int    $status ID status.
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string         Label of status.
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
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

		$question = new Question($this->db);

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);
		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && ! empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Create clone
		$object->fetchQuestionsLinked($object->id, 'digiquali_' . $object->element);
		$object->context['createfromclone'] = 'createfromclone';
		$object->ref = $object->getNextNumRef();
		$object->status = 1;
		$objectid = $object->create($user);

		//add categories
		$cat = new Categorie($this->db);
		$categories = $cat->containing($fromid, 'sheet');

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
		if (is_array($object->linkedObjectsIds['digiquali_question']) && !empty($object->linkedObjectsIds['digiquali_question'])) {
			foreach ($object->linkedObjectsIds['digiquali_question'] as $questionId) {
				$question->fetch($questionId);
				$question->add_object_linked('digiquali_' . $object->element, $objectid);
			}
			$object->updateQuestionsPosition($object->linkedObjectsIds['digiquali_question']);
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a sheet can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isErasable() {
		require_once __DIR__ .'/control.class.php';

		$control = new Control($this->db);

		$controls = $control->fetchAll( '', '', 0, 0, ['customsql' => 't.fk_sheet= ' . $this->id . ' AND t.status >= 0']);
		if (is_array($controls) && !empty($controls)) {
			$result = -1;
		} else {
			$result = 1;
		}

		return $result;
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
	public function selectSheetList($selected = '', $htmlname = 'fk_sheet', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'maxwidth500 widthcentpercentminusxx', $moreparam = '', $multiple = false)
	{
		// phpcs:enable
		global $conf, $user, $langs;

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
		$sql .= " FROM " . MAIN_DB_PREFIX . "digiquali_sheet as s";

		$sql              .= " WHERE s.entity IN (" . getEntity($this->table_element) . ")";
		if ($filter) $sql .= " AND (" . $filter . ")";
		if ($moreparam > 0 ) {
			$children = $this->fetchDigiriskElementFlat($moreparam);
			if ( ! empty($children) && $children > 0) {
				foreach ($children as $key => $value) {
					$sql .= " AND NOT s.rowid =" . $key;
				}
			}
			$sql .= " AND NOT s.rowid =" . $moreparam;
		}

		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this) . "::selectSheetList", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ( ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="minwidth200 flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";

			if ($showempty) {
				$out .= '<option value="-1">&nbsp;</option>';
			}

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
							$out .= '<option value="' . $obj->rowid . '"'.(($obj->status == 2) ? '' : 'disabled').'>' . $label . '</option>';
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

		$this->result = array('nbofsheets' => $num);

		if ($outputmode) return $outarray;
		return $out;
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
		$this->linkedObjectsIds = array();
		$this->linkedObjects = array();

		$justsource = false;
		$withsourcetype = false;

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype, position';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= " WHERE ";
		$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."')";
		$sql .= " AND targettype = 'digiquali_question'";

		$sql .= ' ORDER BY '.$orderby;

		dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$maxPosition = $this->getMaxPosition();
				$obj = $this->db->fetch_object($resql);
				$this->linkedObjectsIds[$obj->targettype][$obj->position ?: ($maxPosition+1)] = $obj->fk_target;
				$i++;
			}
			if (!empty($this->linkedObjectsIds)) {
				$tmparray = $this->linkedObjectsIds;
				foreach ($tmparray as $objecttype => $objectids) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
					// Here $module, $classfile and $classname are set
					if ($loadalsoobjects) {
						foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
							$object = new Question($this->db);
							$ret = $object->fetch($objectid);
							if ($ret >= 0) {
								$this->linkedObjects[$objecttype][$i] = $object;
							}
						}
					}
				}
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Returns max position of questions in sheet
	 *
	 */
	public function getMaxPosition() {
		$sql = "SELECT fk_source, sourcetype, targettype, position FROM ". MAIN_DB_PREFIX ."element_element WHERE fk_source = " . $this->id . " AND sourcetype = 'digiquali_sheet' ORDER BY position DESC LIMIT 1";
		$resql = $this->db->query($sql);

		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$positionField = 'position';
			return $obj->$positionField;
		} else {
			return 0;
		}
	}

	/**
	 *	Update questions position in sheet
	 *
	 *	@param	array	$idsArray			Array containing position and ids of questions in sheet
	 */
	public function updateQuestionsPosition($idsArray)
	{
		$this->db->begin();

		foreach ($idsArray as $position => $questionId) {
			$sql = 'UPDATE '. MAIN_DB_PREFIX . 'element_element';
			$sql .= ' SET position =' . $position;
			$sql .= ' WHERE fk_source = ' . $this->id;
			$sql .= ' AND sourcetype = "digiquali_sheet"';
			$sql .= ' AND fk_target =' . $questionId;
			$sql .= ' AND targettype = "digiquali_question"';
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

	/**
	 * Write information of trigger description
	 *
	 * @param  Object $object Object calling the trigger
	 * @return string         Description to display in actioncomm->note_private
	 */
	public function getTriggerDescription(SaturneObject $object): string
	{
		global $langs;

		$linkedElement = json_decode($object->element_linked, true);

		$ret  = parent::getTriggerDescription($object);
		$ret .= $langs->transnoentities('ElementLinked') . ' : ';

		if (is_array($linkedElement) && !empty($linkedElement)) {
			foreach ($linkedElement as $objectType => $active) {
				$objectTypeUppercase = ucfirst($objectType);

				$ret .= $langs->transnoentities($objectTypeUppercase) . ' ';
			}
		} else {
			$ret .= $langs->transnoentities('NoData');
		}
		$ret .= '</br>';

		return $ret;
	}
}
