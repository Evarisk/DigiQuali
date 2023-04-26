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
 * 	\defgroup   dolismq     Module DoliSMQ
 *  \brief      DoliSMQ module descriptor.
 *
 *  \file       core/modules/modDoliSMQ.class.php
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
		global $conf, $langs;

		$this->db = $db;

		if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
			require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
			saturne_load_langs(['dolismq@dolismq']);
		} else {
			$this->error++;
			$this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'DoliSMQ', 'Saturne');
		}

		// Id for module (must be unique).
		$this->numero = 436301;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'dolismq';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = '';

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		$this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => $langs->trans('Evarisk')]];
		// Module label (no space allowed), used if translation string 'ModuleDoliSMQName' not found (DoliSMQ is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleDoliSMQDesc' not found (DoliSMQ is name of module).
		$this->description = $langs->trans('DoliSMQDescription');
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = $langs->trans('DoliSMQDescriptionLong');

		// Author
		$this->editor_name = 'Evarisk';
		$this->editor_url = 'https://evarisk.com/';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.5.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where DOLISMQ is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'dolismq_color@dolismq';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = [
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
			// Set this to 1 if module has its own models' directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => ['/dolismq/css/scss/modules/_menu.scss'],
			// Set this to relative path of js file if module must load a js on all pages
			'js' => [
				//   '/dolismq/js/dolismq.js',
			],
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => [
				'category',
				'categoryindex',
				'mainloginpage',
                'controlcard'
			],
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		];

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/dolismq/temp","/dolismq/subdir");
		$this->dirs = [
			'/dolismq/temp',
			'/dolismq/question',
			'/ecm/dolismq',
			'/ecm/dolismq/medias',
			'/ecm/dolismq/controldocument'
		];

		// Config pages. Put here list of php page, stored into dolismq/admin directory, to use to set up module.
		$this->config_page_url = ['setup.php@dolismq'];

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = ['modFckeditor', 'modProduct', 'modProductBatch', 'modAgenda', 'modECM', 'modProjet', 'modCategorie', 'modSaturne'];
		$this->requiredby = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = ['dolismq@dolismq'];

		// Prerequisites
		$this->phpmin = [7, 4]; // Minimum version of PHP required by module
		$this->need_dolibarr_version = [15, 0]; // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'DoliSMQWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('DOLISMQ_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('DOLISMQ_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$i = 0;
		$this->const = [
			// CONST SHEET
			$i++ => ['DOLISMQ_SHEET_ADDON', 'chaine', 'mod_sheet_standard', '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_TAGS_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_UNIQUE_LINKED_ELEMENT', 'integer', 1, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_DISPLAY_MEDIAS', 'integer', 1, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_PRODUCT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_PRODUCTLOT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_USER', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_THIRDPARTY', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_CONTACT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_PROJECT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHEET_LINK_TASK', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLISMQ_SHEET_LINK_INVOICE', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLISMQ_SHEET_LINK_ORDER', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLISMQ_SHEET_LINK_CONTRACT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLISMQ_SHEET_LINK_TICKET', 'integer', 0, '', 0, 'current'],

			// CONST QUESTION
			$i++ => ['DOLISMQ_QUESTION_ADDON', 'chaine', 'mod_question_standard', '', 0, 'current'],

			// CONST ANSWER
			$i++ => ['DOLISMQ_ANSWER_ADDON', 'chaine', 'mod_answer_standard', '', 0, 'current'],

			// CONST CONTROL
			$i++ => ['DOLISMQ_CONTROL_ADDON', 'chaine', 'mod_control_standard', '', 0, 'current'],
			$i++ => ['DOLISMQ_CONTROL_USE_LARGE_MEDIA_IN_GALLERY', 'integer', 1, '', 0, 'current'],

			//CONST CONTROL DOCUMENT
			$i++ => ['DOLISMQ_CONTROLDOCUMENT_ADDON', 'chaine', 'mod_controldocument_standard', '', 0, 'current'],
			$i++ => ['DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/dolismq/documents/doctemplates/controldocument/', '', 0, 'current'],
			$i++ => ['DOLISMQ_CONTROLDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/dolismq/controldocument/', '', 0, 'current'],
			$i++ => ['DOLISMQ_CONTROLDOCUMENT_DEFAULT_MODEL', 'chaine', 'template_controldocument_photo' ,'', 0, 'current'],
			$i++ => ['DOLISMQ_CONTROLDOCUMENT_DISPLAY_MEDIAS', 'integer', 1,'', 0, 'current'],
			$i++ => ['DOLISMQ_DOCUMENT_MEDIA_VIGNETTE_USED', 'chaine', 'small','', 0, 'current'],

			// CONST CONTROL LINE
			$i++ => ['DOLISMQ_CONTROLDET_ADDON', 'chaine', 'mod_controldet_standard', '', 0, 'current'],

			// CONST MODULE
			$i++ => ['DOLISMQ_VERSION','chaine', $this->version, '', 0, 'current'],
			$i++ => ['DOLISMQ_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
			$i++ => ['DOLISMQ_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_WIDTH_MINI', 'integer', 128, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_HEIGHT_MINI', 'integer', 72, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_WIDTH_SMALL', 'integer', 480, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_HEIGHT_SMALL', 'integer', 270, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'],
			$i++ => ['DOLISMQ_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'],
			$i++ => ['DOLISMQ_DISPLAY_NUMBER_MEDIA_GALLERY', 'integer', 8, '', 0, 'current'],
			$i++ => ['DOLISMQ_REDIRECT_AFTER_CONNECTION', 'integer', 0, '', 0, 'current'],

			// CONST DOCUMENTS
			$i++ => ['MAIN_ODT_AS_PDF', 'chaine', 'libreoffice', '', 0, 'current'],
		];

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
		$this->tabs   = [];
		$pictopath    = dol_buildpath('/custom/dolismq/img/dolismq_color.png', 1);
		$pictoDoliSMQ = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');
		$this->tabs[] = ['data' => 'productlot:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=productbatch'];
		$this->tabs[] = ['data' => 'product:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=product'];
		$this->tabs[] = ['data' => 'project:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=project'];
		$this->tabs[] = ['data' => 'thirdparty:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=societe'];
		$this->tabs[] = ['data' => 'contact:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=contact'];
		$this->tabs[] = ['data' => 'task:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=project_task'];
		$this->tabs[] = ['data' => 'user:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=user'];
		$this->tabs[] = ['data' => 'invoice:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=facture'];
		$this->tabs[] = ['data' => 'order:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=commande'];
		$this->tabs[] = ['data' => 'contract:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=contrat'];
		$this->tabs[] = ['data' => 'ticket:+control:' . $pictoDoliSMQ . $langs->trans('Controls') . ':dolismq@dolismq:$user->rights->dolismq->control->read:/custom/dolismq/view/control/control_list.php?fromid=__ID__&fromtype=ticket'];

        // Dictionaries.
        $this->dictionaries = [
            'langs' => 'dolismq@dolismq',
            // List of tables we want to see into dictonnary editor.
            'tabname' => [
                MAIN_DB_PREFIX . 'c_question_type',
                MAIN_DB_PREFIX . 'c_control_attendants_role'
            ],
            // Label of tables.
            'tablib' => [
                'Question',
                'Control'
            ],
            // Request to select fields.
            'tabsql' => [
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active  FROM ' . MAIN_DB_PREFIX . 'c_question_type as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_control_attendants_role as f'
            ],
            // Sort order.
            'tabsqlsort' => [
                'label ASC',
                'label ASC'
            ],
            // List of fields (result of select to show dictionary).
            'tabfield' => [
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields to edit a record).
            'tabfieldvalue' => [
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields for insert).
            'tabfieldinsert' => [
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // Name of columns with primary key (try to always name it 'rowid').
            'tabrowid' => [
                'rowid',
                'rowid'
            ],
            // Condition to show each dictionary.
            'tabcond' => [
                $conf->dolismq->enabled,
                $conf->dolismq->enabled
            ]
        ];

		// Boxes/Widgets
		// Add here list of php file(s) stored in dolismq/core/boxes that contains a class to show a widget.
		$this->boxes = [];

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = [];

		// Permissions provided by this module
		$this->rights = [];
		$r = 0;

		/* DOLISMQ PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('LireModule', 'DoliSMQ');
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = 1;
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadModule', 'DoliSMQ');
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = 1;
		$r++;

		/* CONTROL PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('ControlsMin')); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('ControlsMin')); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('ControlsMin')); // Permission label
		$this->rights[$r][4] = 'control'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* QUESTION PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('Questions')); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('Questions')); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('Questions')); // Permission label
		$this->rights[$r][4] = 'question'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* SHEET PERMISSSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('Sheets')); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('Sheets')); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('Sheets')); // Permission label
		$this->rights[$r][4] = 'sheet'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolismq->level1->level2)
		$r++;

		/* ADMINPAGE PANEL ACCESS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'DoliSMQ');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('ChangeUserController');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'changeusercontroller';

		// Main menu entries to add
		$this->menu = [];
		$r = 0;

		// Add here entries to declare new menus
		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'top',
			'titre'    => $langs->trans('DoliSMQ'),
			'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
			'mainmenu' => 'dolismq',
			'leftmenu' => '',
			'url'      => '/dolismq/dolismqindex.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $user->rights->dolismq->lire',
			'perms'    => '$user->rights->dolismq->lire',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => $langs->transnoentities('Question'),
			'prefix'   => '<i class="fas fa-question pictofixedwidth"></i>',
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_question',
			'url'      => '/dolismq/view/question/question_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $user->rights->dolismq->question->read',
			'perms'    => '$user->rights->dolismq->question->read',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq,fk_leftmenu=dolismq_question',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_questiontags',
			'url'      => '/categories/index.php?type=question',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $conf->categorie->enabled && $user->rights->dolismq->question->read',
			'perms'    => '$user->rights->dolismq->question->read',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => $langs->transnoentities('Sheet'),
			'prefix'   => '<i class="fas fa-list pictofixedwidth"></i>',
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_sheet',
			'url'      => '/dolismq/view/sheet/sheet_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $user->rights->dolismq->sheet->read',
			'perms'    => '$user->rights->dolismq->sheet->read',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq,fk_leftmenu=dolismq_sheet',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_sheettags',
			'url'      => '/categories/index.php?type=sheet',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $conf->categorie->enabled && $user->rights->dolismq->sheet->read',
			'perms'    => '$user->rights->dolismq->sheet->read',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq',
			'type'     => 'left',
			'titre'    => $langs->transnoentities('Control'),
			'prefix'   => '<i class="fas fa-tasks pictofixedwidth"></i>',
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_control',
			'url'      => '/dolismq/view/control/control_list.php',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $user->rights->dolismq->control->read',
			'perms'    => '$user->rights->dolismq->control->read',
			'target'   => '',
			'user'     => 0,
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolismq,fk_leftmenu=dolismq_control',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
			'mainmenu' => 'dolismq',
			'leftmenu' => 'dolismq_controltags',
			'url'      => '/categories/index.php?type=control',
			'langs'    => 'dolismq@dolismq',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolismq->enabled && $conf->categorie->enabled && $user->rights->dolismq->control->read',
			'perms'    => '$user->rights->dolismq->control->read',
			'target'   => '',
			'user'     => 0,
		];
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = ''): int
	{
		global $conf;

		if ($this->error > 0) {
			setEventMessages('', $this->errors, 'errors');
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		$sql    = [];
		$result = $this->_load_tables('/dolismq/sql/');

		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/dolismq/sql/' . $subFolder . '/');
			}
		}

		dolibarr_set_const($this->db, 'DOLISMQ_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'DOLISMQ_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

		delDocumentModel('controldocument_odt', 'controldocument');
		delDocumentModel('calypso_controldocument', 'controldocument');

		addDocumentModel('controldocument_odt', 'controldocument', 'ODT templates', 'DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('calypso_controldocument', 'controldocument', 'calypso');

		if ($result < 0) {
			return -1;
		} // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

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
	public function remove($options = ''): int
	{
		$sql = [];
		return $this->_remove($sql, $options);
	}
}
