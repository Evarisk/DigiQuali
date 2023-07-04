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
 * \file    class/dolibarrobject.class.php
 * \ingroup dolismq
 * \brief   This file is a CRUD class file for all DolibarrObject (Create/Read/Update/Delete).
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * Class for DoliSMQExpedition.
 */
class DoliSMQExpedition extends Expedition
{
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
        'rowid'              => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'tms'                => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 10,  'notnull' => 0, 'visible' => -1],
        'ref'                => ['type' => 'varchar(30)',  'label' => 'Ref',              'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'entity'             => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -1, 'index' => 1],
        'fk_soc'             => ['type' => 'integer:Societe:societe/class/societe.class.php',              'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 40,  'notnull' => 1, 'visible' => -1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'societe.rowid'],
        'fk_projet'          => ['type' => 'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label' => 'Project',    'picto' => 'projet',  'enabled' => '$conf->project->enabled', 'position' => 50,  'notnull' => 0, 'visible' => -1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'projet.rowid'],
        'ref_ext'            => ['type' => 'varchar(255)', 'label' => 'RefExt',       'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -1],
        'ref_int'            => ['type' => 'varchar(255)', 'label' => 'RefInt',       'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => -1],
        'ref_customer'       => ['type' => 'varchar(255)', 'label' => 'RefCustomer',  'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => -1],
        'date_creation'      => ['type' => 'datetime',     'label' => 'DateCreation', 'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => -1],
        'fk_user_author'     => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'date_valid'         => ['type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'position' => 120,  'notnull' => 0, 'visible' => 5],
        'fk_user_valid'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'picto' => 'user', 'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'date_delivery'      => ['type' => 'datetime',     'label' => 'DateDelivery',   'enabled' => 1,                          'position' => 140, 'notnull' => 0, 'visible' => -1],
        'date_expedition'    => ['type' => 'datetime',     'label' => 'DateExpedition', 'enabled' => 1,                          'position' => 150, 'notnull' => 0, 'visible' => -1],
        'fk_address'         => ['type' => 'integer',      'label' => 'Address',        'enabled' => 1,                          'position' => 160, 'notnull' => 0, 'visible' => -1],
        'fk_shipping_method' => ['type' => 'integer',      'label' => 'ShippingMethod', 'enabled' => 1,                          'position' => 170, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'default' => 0],
        'tracking_number'    => ['type' => 'varchar(50)',  'label' => 'TrackingNumber', 'enabled' => 1,                          'position' => 180, 'notnull' => 0, 'visible' => -1],
        'fk_statut'          => ['type' => 'smallint(6)',  'label' => 'Status',         'enabled' => 1,                          'position' => 190, 'notnull' => 0, 'visible' => -1, 'index' => 1, 'default' => 0],
        'billed'             => ['type' => 'smallint(6)',  'label' => 'Billed',         'enabled' => 1,                          'position' => 200, 'notnull' => 0, 'visible' => -1],
        'height'             => ['type' => 'float',        'label' => 'Height',         'enabled' => 1,                          'position' => 210, 'notnull' => 0, 'visible' => -1],
        'width'              => ['type' => 'float',        'label' => 'Width',          'enabled' => 1,                          'position' => 220, 'notnull' => 0, 'visible' => -1],
        'size_units'         => ['type' => 'integer',      'label' => 'SizeUnits',      'enabled' => 1,                          'position' => 230, 'notnull' => 0, 'visible' => -1],
        'size'               => ['type' => 'float',        'label' => 'Size',           'enabled' => 1,                          'position' => 240, 'notnull' => 0, 'visible' => -1],
        'weight_units'       => ['type' => 'integer',      'label' => 'WeightUnits',    'enabled' => 1,                          'position' => 250, 'notnull' => 0, 'visible' => -1],
        'weight'             => ['type' => 'float',        'label' => 'Weight',         'enabled' => 1,                          'position' => 260, 'notnull' => 0, 'visible' => -1],
        'note_private'       => ['type' => 'text',         'label' => 'NotePrivate',    'enabled' => 1,                          'position' => 270, 'notnull' => 0, 'visible' => -1],
        'note_public'        => ['type' => 'text',         'label' => 'NotePublic',     'enabled' => 1,                          'position' => 280, 'notnull' => 0, 'visible' => -1],
        'model_pdf'          => ['type' => 'varchar(255)', 'label' => 'PDFTemplate',    'enabled' => 1,                          'position' => 290, 'notnull' => 0, 'visible' => -1],
        'last_main_doc'      => ['type' => 'varchar(255)', 'label' => 'LastMainDoc',    'enabled' => 1,                          'position' => 300, 'notnull' => 0, 'visible' => -1],
        'fk_incoterms'       => ['type' => 'integer',      'label' => 'IncotermCode',   'enabled' => '$conf->incoterm->enabled', 'position' => 310, 'notnull' => 0, 'visible' => -1],
        'location_incoterms' => ['type' => 'varchar(255)', 'label' => 'IncotermLabel',  'enabled' => '$conf->incoterm->enabled', 'position' => 320, 'notnull' => 0, 'visible' => -1],
        'import_key'         => ['type' => 'varchar(14)',  'label' => 'ImportId',       'enabled' => 1,                          'position' => 330, 'notnull' => 0, 'visible' => -1],
        'extraparams'        => ['type' => 'varchar(255)', 'label' => 'ExtraParams',    'enabled' => 1,                          'position' => 340, 'notnull' => 0, 'visible' => -1]
    ];
}

/**
 * Class for DoliSMQExpedition.
 */
class DoliSMQSupplierProposal extends SupplierProposal
{
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
    public array $fields = [
        'rowid'              => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'tms'                => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 10,  'notnull' => 0, 'visible' => -1],
        'ref'                => ['type' => 'varchar(30)',  'label' => 'Ref',              'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => -1, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'entity'             => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -1, 'index' => 1]
    ];
}

/**
 * Class for DoliSMQContact.
 */
class DoliSMQContact extends Contact
{
    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);

        $this->fields['fk_soc']['enabled'] = 1;
    }
}
