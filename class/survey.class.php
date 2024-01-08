<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    class/survey.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Survey (Create/Read/Update/Delete)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Survey
 */
class Survey extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'survey';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_survey';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for survey. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'survey@digiquali' if picto is file 'img/object_survey.png'
     */
    public string $picto = 'fontawesome_fa-marker_fas_#d35968';

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
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2, 'positioncard' => 10],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => 0, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'Validated', 2 => 'Locked', 3 => 'Archived']],
        'note_public'   => ['type' => 'html',         'label' => 'NotePublic',       'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'note_private'  => ['type' => 'html',         'label' => 'NotePrivate',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'photo'         => ['type' => 'text',         'label' => 'Photo',            'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0],
        'track_id'      => ['type' => 'text',         'label' => 'TrackID',          'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php',           'label' => 'UserAuthor',  'picto' => 'user',                            'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php',           'label' => 'UserModif',   'picto' => 'user',                            'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_sheet'      => ['type' => 'integer:Sheet:digiquali/class/sheet.class.php',    'label' => 'Sheet',       'picto' => 'fontawesome_fa-list_fas_#d35968', 'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'digiquali_sheet.rowid'],
        'projectid'     => ['type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project',     'picto' => 'project',                         'enabled' => 1, 'position' => 13,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'projet.rowid', 'positioncard' => 2]
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string Public note
     */
    public $note_public;

    /**
     * @var string Private note
     */
    public $note_private;

    /**
     * @var string|null Photo path
     */
    public ?string $photo = '';

    /**
     * @var string|null TrackID
     */
    public ?string $track_id;

    /**
     * @var int User ID
     */
    public int $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public ?int $fk_user_modif;

    /**
     * @var int Sheet ID
     */
    public int $fk_sheet;

    /**
     * @var int|string|null Project ID
     */
    public $projectid;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false): int
    {
        $this->track_id = generate_random_id();
        $result = parent::create($user, $notrigger);

        if ($result > 0) {
            global $conf;

            require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';

            $url = dol_buildpath('custom/digiquali/public/survey/public_survey.php?track_id=' . $this->track_id . '&entity=' . $conf->entity, 3);

            $barcode = new TCPDF2DBarcode($url, 'QRCODE,L');

            dol_mkdir($conf->digiquali->multidir_output[$conf->entity] . '/survey/' . $this->ref . '/qrcode/');
            $file = $conf->digiquali->multidir_output[$conf->entity] . '/survey/' . $this->ref . '/qrcode/' . 'barcode_' . $this->track_id . '.png';

            $imageData = $barcode->getBarcodePngData();
            $imageData = imagecreatefromstring($imageData);
            imagepng($imageData, $file);
        }

        return $result;
    }

    // @todo pas fait
    /**
     * Set draft status
     *
     * @param  User $user      Object user that modify
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @return int             0 < if KO, > 0 if OK
     * @throws Exception
     */
    public function setDraft(User $user, int $notrigger = 0): int
    {
        // Protection
        if ($this->status <= self::STATUS_DRAFT) {
            return 0;
        }

        $signatory = new SaturneSignature($this->db);
        $signatory->deleteSignatoriesSignatures($this->id, 'control');

        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CONTROL_UNVALIDATE');
    }

    /**
     * Return if a survey can be deleted
     *
     * @return int 0 < if KO, > 0 if OK
     */
    public function isErasable(): int
    {
        return $this->isLinkedToOtherObjects();
    }

    /**
     * Return if a survey is linked to another object
     *
     * @return int 0 < if KO, > 0 if OK
     */
    public function isLinkedToOtherObjects(): int
    {
        // Links between objects are stored in table element_element
        $sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
        $sql .= ' FROM '.MAIN_DB_PREFIX . 'element_element';
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

    // @todo pas fait
    /**
     * Clone an object into another one.
     *
     * @param  User      $user    User that creates
     * @param  int       $fromID  ID of object to clone.
     * @param  array     $options Options array.
     * @return int                New object created, <0 if KO.
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromID, array $options): int
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        $object = new self($this->db);
        $this->db->begin();

        // Load source object.
        $result = $object->fetchCommon($fromID);
        if ($result > 0 && ! empty($object->table_element_line)) {
            $object->fetchLines();
        }

        $objectRef = $object->ref;

        // Reset some properties.
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields.
        if (property_exists($object, 'ref')) {
            $object->ref = '';
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'status')) {
            $object->status = 0;
        }
        if (property_exists($object, 'verdict')) {
            $object->verdict = 0;
        }
        if (empty($options['photos'])) {
            $object->photo = '';
        }
        if (property_exists($object, 'control_date')) {
            $object->control_date = '';
        }
        if (property_exists($object, 'next_control_date')) {
            $object->next_control_date = '';
        }

        $object->context = 'createfromclone';

        $object->fetchObjectLinked('','', $object->id, 'digiquali_' . $object->element,  'OR', 1, 'sourcetype', 0);

        $controlID = $object->create($user);

        if ($controlID > 0) {
            $objectFromClone = new self($this->db);
            $objectFromClone->fetch($controlID);

            // Categories.
            $cat = new Categorie($this->db);
            $categories = $cat->containing($fromID, 'control');
            if (is_array($categories) && !empty($categories)) {
                foreach($categories as $cat) {
                    $categoryIds[] = $cat->id;
                }
                $object->setCategories($categoryIds);
            }

            // Add objects linked.
			$linkableElements = get_sheet_linkable_objects();

			if (!empty($linkableElements)) {
				foreach($linkableElements as $linkableElement) {
                    if ($linkableElement['conf'] > 0 && (!empty($object->linkedObjectsIds[$linkableElement['link_name']]))) {
						foreach($object->linkedObjectsIds[$linkableElement['link_name']] as $linkedElementId) {
							$objectFromClone->add_object_linked($linkableElement['link_name'], $linkedElementId);
						}
					}
				}
			}

            // Add Attendants.
            $signatory = new SaturneSignature($this->db);
            if (!empty($options['attendants'])) {
                // Load signatory from source object.
                $signatories = $signatory->fetchSignatory('', $fromID, $this->element);
                if (is_array($signatories) && !empty($signatories)) {
                    foreach ($signatories as $arrayRole) {
                        foreach ($arrayRole as $signatoryRole) {
                            $signatory->createFromClone($user, $signatoryRole->id, $controlID);
                        }
                    }
                }
            } else {
                $signatory->setSignatory($objectFromClone->id, $this->element, 'user', [$objectFromClone->fk_user_controller], 'Controller', 1);
            }

            // Add Photos.
            if (!empty($options['photos'])) {
                $dir  = $conf->digiquali->multidir_output[$conf->entity] . '/control';
                $path = $dir . '/' . $objectRef . '/photos';
                dol_mkdir($dir . '/' . $objectFromClone->ref . '/photos');
                dolCopyDir($path,$dir . '/' . $objectFromClone->ref . '/photos', 0, 1);
            }
        } else {
            $error++;
            $this->error  = $object->error;
            $this->errors = $object->errors;
        }

        // End.
        if (!$error) {
            $this->db->commit();
            return $controlID;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    // @todo pas fait
    /**
     * Return the status
     *
     * @param  int    $status ID status
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string         Label of status
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_DRAFT]          = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatus[self::STATUS_LOCKED]         = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]       = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]        = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_LOCKED) {
            $statusType = 'status6';
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
     * Initialise object with example values
     * ID must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->initAsSpecimenCommon();
    }

    /**
     * Create an array of lines
     *
     * 	@return array|int array of lines if OK, < 0 if KO
     */
    public function getLinesArray()
    {
        $this->lines = [];

        $objectLine = new SurveyLine($this->db);
        $result     = $objectLine->fetchAll('ASC', 'position', 0, 0, ['customsql' => 'fk_survey = ' . $this->id]);

        if (is_numeric($result)) {
            $this->error  = $objectLine->error;
            $this->errors = $objectLine->errors;
            return $result;
        } else {
            $this->lines = $result;
            return $this->lines;
        }
    }

    // @todo pas fait
    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        $getNbControlsTagsByVerdict   = $this->getNbControlsTagsByVerdict();
        $getNbControlsByVerdict       = $this->getNbControlsByVerdict();
        $getNbControlsByMonth         = $this->getNbControlsByMonth();
        $getControlListsByNextControl = $this->getControlListsByNextControl();

        $array['graphs'] = [$getNbControlsTagsByVerdict, $getNbControlsByVerdict, $getNbControlsByMonth];
        $array['lists']  = [$getControlListsByNextControl];

        return $array;
    }

    // @todo pas fait
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
        $array['width']   = '100%';
        $array['height']  = 400;
        $array['type']    = 'pie';
        $array['dataset'] = 1;

        $array['labels'] = [
            0 => [
                'label' => 'N/A',
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

        $arrayNbControlByVerdict = [0 => 0, 1 => 0, 2 => 0];
        $controls = $this->fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0']);
        if (is_array($controls) && !empty($controls)) {
            foreach ($controls as $control) {
                if (empty($control->verdict)) {
                    $arrayNbControlByVerdict[0]++;
                } else {
                    $arrayNbControlByVerdict[$control->verdict]++;
                }
            }
            ksort($arrayNbControlByVerdict);
        }

        $array['data'] = $arrayNbControlByVerdict;

        return $array;
    }

    // @todo pas fait
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
        $array['width']   = '100%';
        $array['height']  = 400;
        $array['type']    = 'bar';
        $array['dataset'] = 3;

        $array['labels'] = [
            0 => [
                'label' => 'N/A',
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
                $controls = $this->fetchAll('', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $category->id . ' AND t.status >= 0'], 'AND', true);
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

    // @todo pas fait
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
        $array['width']   = '100%';
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
                $controls = $this->fetchAll('', '', 0, 0, ['customsql' => 'MONTH (t.date_creation) = ' . $i . ' AND YEAR (t.date_creation) = ' . $year . ' AND t.status >= 0']);
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

    // @todo pas fait
    /**
     * Get controls list by next control.
     *
     * @return array     Graph datas (label/color/type/title/data etc..).
     * @throws Exception
     */
    public function getControlListsByNextControl(): array
    {
        global $langs;

        // Graph Title parameters.
        $array['title'] = $langs->transnoentities('ControlListsByNextControl');
        $array['picto'] = $this->picto;

        // Graph parameters.
        $array['type']   = 'list';
        $array['labels'] = ['Ref', 'LinkedObject', 'Controller', 'Project', 'Sheet', 'ControlDate', 'NextControl', 'Verdict'];

        $arrayControlListsByNextControl = [];

        $elementArray = get_sheet_linkable_objects();
        $controls     = $this->fetchAll('ASC', 'next_control_date', 10, 0, ['customsql' => 't.status = ' . self::STATUS_LOCKED . ' AND t.next_control_date IS NOT NULL']);
        if (is_array($controls) && !empty($controls)) {
            foreach ($controls as $control) {
                $control->fetchObjectLinked('', '', $control->id, 'digiquali_control', 'OR', 1, 'sourcetype', 0);
                $linkedObjectsInfos = $control->getLinkedObjectsWithQcFrequency($elementArray);
                $linkedObjects      = $linkedObjectsInfos['linkedObjects'];
                $qcFrequencyArray   = $linkedObjectsInfos['qcFrequencyArray'];
                foreach ($elementArray as $linkableObjectType => $linkableObject) {
                    if (is_object($linkedObjects[$linkableObjectType])) {
                        if ($linkableObject['conf'] > 0 && (!empty($control->linkedObjectsIds[$linkableObject['link_name']]))) {
                            $currentObject = $linkedObjects[$linkableObjectType];
                            if ($qcFrequencyArray[$linkableObjectType] > 0) {
                                require_once __DIR__ . '/sheet.class.php';

                                $userTmp = new User($this->db);
                                $project = new Project($this->db);
                                $sheet   = new Sheet($this->db);

                                $userTmp->fetch($control->fk_user_controller);
                                $project->fetch($control->projectid);
                                $sheet->fetch($control->fk_sheet);

                                if (!empty($control->next_control_date)) {
                                    $nextControl      = floor(($control->next_control_date - dol_now('tzuser'))/(3600 * 24));
                                    $nextControlColor = $nextControl < 0 ? 'red' : ($nextControl <= 30 ? 'orange' : ($nextControl <= 60 ? 'yellow' : 'green'));

                                    $verdictColor = $control->verdict == 1 ? 'green' : ($control->verdict == 2 ? 'red' : 'grey');

                                    $arrayControlListsByNextControl[$control->id]['Ref']['value']            = $control->getNomUrl(1);
                                    $arrayControlListsByNextControl[$control->id]['LinkedObject']['value']   = $currentObject->getNomUrl(1);
                                    $arrayControlListsByNextControl[$control->id]['UserController']['value'] = $userTmp->getNomUrl(1);
                                    $arrayControlListsByNextControl[$control->id]['Project']['value']        = $project->id > 0 ? $project->getNomUrl(1) : '';
                                    $arrayControlListsByNextControl[$control->id]['Sheet']['value']          = $sheet->getNomUrl(1);
                                    $arrayControlListsByNextControl[$control->id]['ControlDate']['value']    = dol_print_date($control->date_creation, 'day');
                                    $arrayControlListsByNextControl[$control->id]['NextControl']['value']    = '<div class="wpeo-button button-'. $nextControlColor .'">' . $nextControl . '<br>' . $langs->trans('Days') . '</div>';
                                    $arrayControlListsByNextControl[$control->id]['NextControl']['morecss']  = 'dashboard-control';
                                    $arrayControlListsByNextControl[$control->id]['Verdict']['value']        = '<div class="wpeo-button button-'. $verdictColor .'">' . $control->fields['verdict']['arrayofkeyval'][(!empty($control->verdict)) ?: 3] . '</div>';
                                    $arrayControlListsByNextControl[$control->id]['Verdict']['morecss']      = 'dashboard-control';
                                }
                            }
                        }
                    }
                }
            }
        }
        $array['data'] = $arrayControlListsByNextControl;

        return $array;
    }

    /**
     * Write information of trigger description
     *
     * @param  SaturneObject $object Object calling the trigger
     * @return string                Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        // Load DigiQuali libraries
        require_once __DIR__ . '/../class/sheet.class.php';

        $sheet = new Sheet($this->db);
        $sheet->fetch($object->fk_sheet);

        $ret  = parent::getTriggerDescription($object);
        $ret .= $langs->transnoentities('Sheet') . ' : ' . $sheet->ref . ' - ' . $sheet->label . '</br>';
        if ($object->projectid > 0) {
            require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
            $project = new Project($this->db);
            $project->fetch($object->projectid);
            $ret .= $langs->transnoentities('Project') . ' : ' . $project->ref . ' ' . $project->title . '</br>';
        }
        $ret .= (!empty($object->photo) ? $langs->transnoentities('Photo') . ' : ' . $object->photo . '</br>' : '');

        return $ret;
    }
}

class SurveyLine extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string ID to identify managed object
     */
    public $element = 'surveydet';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'digiquali_surveydet';

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
		'fk_survey'        => array('type' => 'integer', 'label' => 'FkControl', 'enabled' => '1', 'position' => 100, 'notnull' => 1, 'visible' => 0,),
	);

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    // @todo pas fait
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
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiquali_controldet as t';
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

    // @todo pas fait
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
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiquali_controldet as t';
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
}
