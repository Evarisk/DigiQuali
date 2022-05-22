<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2022 Eoxia <dev@eoxia.com>
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
 * 	\defgroup   dolismq     Module DoliSMQ
 *  \brief      DoliSMQ module descriptor.
 *
 *  \file       htdocs/dolismq/core/modules/modDoliSMQ.class.php
 *  \ingroup    dolismq
 *  \brief      Description and activation file for module DoliSMQ
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DoliSMQ
 */
class modDoliSMQ extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 436301; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'dolismq';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		$this->familyinfo = array('Evarisk' => array('position' => '01', 'label' => $langs->trans("Evarisk")));
		// Module label (no space allowed), used if translation string 'ModuleDoliSMQName' not found (DoliSMQ is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleDoliSMQDesc' not found (DoliSMQ is name of module).
		$this->description = $langs->trans("DoliSMQDescription");
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = $langs->trans("DoliSMQDescriptionLong");

		// Author
		$this->editor_name = 'Evarisk';
		$this->editor_url = 'https://evarisk.com/';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.2.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where DOLISMQ is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'dolismq@dolismq';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/dolismq/css/dolismq.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/dolismq/js/dolismq.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/dolismq/temp","/dolismq/subdir");
		$this->dirs = array(
			"/dolismq/temp",
			"/dolismq/question",
			"/ecm/dolismq",
			"/ecm/dolismq/medias"
		);


		// Config pages. Put here list of php page, stored into dolismq/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@dolismq");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('modFckeditor', 'modProduct', 'modProductBatch', 'modAgenda', 'modECM', 'modProjet');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("dolismq@dolismq");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(13, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'DoliSMQWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('DOLISMQ_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('DOLISMQ_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			// CONST SHEET
			1 => array('DOLISMQ_SHEET_ADDON', 'chaine', 'mod_sheet_standard', '', 0, 'current'),

			// CONST QUESTION
			10 => array('DOLISMQ_QUESTION_ADDON', 'chaine', 'mod_question_standard', '', 0, 'current'),

			// CONST CONTROL
			20 => array('DOLISMQ_CONTROL_ADDON', 'chaine', 'mod_control_standard', '', 0, 'current'),
			21 => array('DOLISMQ_CONTROL_DISPLAY_MEDIAS', 'integer', 1, '', 0, 'current'),
			//22 => array('DOLISMQ_CONTROL_SET_USER_CONTROLLER', 'integer', 0, '', 0, 'current'),
			23 => array('DOLISMQ_CONTROL_SHOW_PRODUCT', 'integer', 0, '', 0, 'current'),
			24 => array('DOLISMQ_CONTROL_SHOW_PRODUCTLOT', 'integer', 0, '', 0, 'current'),
			25 => array('DOLISMQ_CONTROL_SHOW_THIRDPARTY', 'integer', 0, '', 0, 'current'),
			26 => array('DOLISMQ_CONTROL_SHOW_PROJECT', 'integer', 0, '', 0, 'current'),
			27 => array('DOLISMQ_CONTROL_SHOW_TASK', 'integer', 0, '', 0, 'current'),

			//CONST CONTROL DOCUMENT
			30 => array('DOLISMQ_CONTROLDOCUMENT_ADDON', 'chaine', 'mod_controldocument_standard', '', 0, 'current'),
			31 => array('DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH', 'chaine', DOL_DOCUMENT_ROOT . '/custom/dolismq/documents/doctemplates/controldocument/', '', 0, 'current'),
			32 => array('DOLISMQ_CONTROLDOCUMENT_DEFAULT_MODEL', 'chaine', 'template_controldocument_photo' ,'', 0, 'current'),
			33 => array('DOLISMQ_CONTROLDOCUMENT_DISPLAY_MEDIAS', 'integer', 1,'', 0, 'current'),
			34 => array('DOLISMQ_CONTROLDOCUMENT_ADDON_PDF', 'chaine', 'calypso', '', 0, 'current'),

			// CONST CONTROL LINE
			40 => array('DOLISMQ_CONTROLDET_ADDON', 'chaine', 'mod_controldet_standard', '', 0, 'current'),

		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->dolismq) || !isset($conf->dolismq->enabled)) {
			$conf->dolismq = new stdClass();
			$conf->dolismq->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		$this->tabs[]    = array('data' => 'productlot:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=productbatch');
		$this->tabs[]    = array('data' => 'product:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=product'); // To add a new tab identified by code tabname1
		$this->tabs[]    = array('data' => 'project:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=project');  					// To add a new tab identified by code tabname1
		$this->tabs[]    = array('data' => 'thirdparty:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=societe');  					// To add a new tab identified by code tabname1
		$this->tabs[]    = array('data' => 'task:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=project_task');  					// To add a new tab identified by code tabname1
		$this->tabs[]    = array('data' => 'user:+control:Control:dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=user');  					// To add a new tab identified by code tabname1

		// Dictionaries
		$this->dictionaries = array();
		/* Example:
		$this->dictionaries=array(
			'langs'=>'dolismq@dolismq',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),
			// Label of tables
			'tablib'=>array("Table1", "Table2", "Table3"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->dolismq->enabled, $conf->dolismq->enabled, $conf->dolismq->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in dolismq/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'dolismqwidget1.php@dolismq',
			//      'note' => 'Widget provided by DoliSMQ',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/dolismq/class/audit.class.php',
			//      'objectname' => 'Audit',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->dolismq->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->dolismq->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->dolismq->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;

		// Add here entries to declare new permissions

		/* module PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('LireDoliSMQ');
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = 1;
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadDoliSMQ');
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = 1;
		$r++;

		/* CONTROL PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('ReadControl'); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('CreateControl'); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('DeleteControl'); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* QUESTION PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('ReadQuestion'); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('CreateQuestion'); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('DeleteQuestion'); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* SHEET PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('ReadSheet'); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('CreateSheet'); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('DeleteSheet'); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* ADMINPAGE PANEL ACCESS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadAdminPage');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('ChangeUserController');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'changeusercontroller';

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		$langs->load("dolismq@dolismq");

		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++]=array(
			'fk_menu'  => '', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'top', // This is a Top menu entry
			'titre'    => 'DoliSMQ',
			'mainmenu' => 'dolismq',
			'leftmenu' => '',
			'url'      => '/dolismq/dolismqindex.php',
			'langs'    => 'dolismq@dolismq', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled', // Define condition to show or hide menu entry. Use '$conf->dolismq->enabled' if entry must be visible if module is enabled.
			'perms'    => '$user->rights->dolismq->lire',  // Use 'perms'=>'$user->rights->dolismq->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-question"></i>  ' . $langs->trans('Question'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_question',
			'url'      => '/dolismq/view/question/question_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolismq->enabled',
			'perms'    => '$user->rights->dolismq->question->read',
			'target'   => '',
			'user'     => 2,
		);

//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=dolismq,fk_leftmenu=dolismq_question',
//			'type'=>'left',
//			'titre'=>$langs->trans('AddQuestion'),
//			'mainmenu'=>'dolismq',
//			'leftmenu'=>'dolismq_question',
//			'url'=>'/dolismq/view/question/question_card.php?action=create',
//			'langs'=>'dolismq@dolismq',
//			'position'=>1100+$r,
//			'enabled'=>'$conf->dolismq->enabled',
//			'perms'=>'$user->rights->dolismq->question->write',
//			'target'=>'',
//			'user'=>2
//		);

		$this->menu[$r++]=array(
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-list"></i>  ' . $langs->trans('Sheet'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_sheet',
			'url'      => '/dolismq/view/sheet/sheet_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolismq->enabled',
			'perms'    => '$user->rights->dolismq->sheet->read',
			'target'   => '',
			'user'     => 2,
		);

//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=dolismq,fk_leftmenu=dolismq_sheet',
//			'type'=>'left',
//			'titre'=>$langs->trans('AddSheet'),
//			'mainmenu'=>'dolismq',
//			'leftmenu'=>'dolismq_sheet',
//			'url'=>'/dolismq/view/sheet/sheet_card.php?action=create',
//			'langs'=>'dolismq@dolismq',
//			'position'=>1100+$r,
//			'enabled'=>'$conf->dolismq->enabled',
//			'perms'=>'$user->rights->dolismq->sheet->write',
//			'target'=>'',
//			'user'=>2
//		);

		$this->menu[$r++]=array(
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tasks"></i>  ' . $langs->trans('Control'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_control',
			'url'      => '/dolismq/view/control/control_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolismq->enabled',
			'perms'    => '$user->rights->dolismq->control->read',
			'target'   => '',
			'user'     => 2,
		);

//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=dolismq,fk_leftmenu=dolismq_control',
//			'type'=>'left',
//			'titre'=>$langs->trans('AddControl'),
//			'mainmenu'=>'dolismq',
//			'leftmenu'=>'dolismq_control',
//			'url'=>'/dolismq/view/control/control_card.php?action=create',
//			'langs'=>'dolismq@dolismq',
//			'position'=>1100+$r,
//			'enabled'=>'$conf->dolismq->enabled',
//			'perms'=>'$user->rights->dolismq->control->write',
//			'target'=>'',
//			'user'=>2
//		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolismq',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => '<i class="fas fa-cog"></i>  ' . $langs->trans('DoliSMQConfig'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq',
			'url'      => '/dolismq/admin/setup.php',
			'langs'    => 'dolismq@dolismq',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolismq->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->dolismq->adminpage->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => '<span class="minimizeMenu" title="'. $langs->transnoentities('MinimizeMenu') .'"><i class="fas fa-bars"></i>  ' . $langs->transnoentities('MinimizeMenu') . '</span>',
			'mainmenu' => 'dolismq',
			'leftmenu' => '',
			'url'      => '',
			'langs'    => 'dolismq@dolismq',
			'position' => 1100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',
			'perms'    => 1,
			'target'   => '',
			'user'     => 0,
		);
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$sql = array();
		$result = $this->_load_tables('/dolismq/sql/');

		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/dolismq/sql/' . $subFolder . '/');
			}
		}

		delDocumentModel('controldocument_odt', 'controldocument');

		addDocumentModel('controldocument_odt', 'controldocument', 'ODT templates', 'DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH');

		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		// Permissions
		$this->remove($options);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
