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
 *	\file       core/modules/dolismq/dolismqdocumets/controldocument/doc_controldocument_odt.modules.php
 *	\ingroup    dolismq
 *	\brief      File of class to build ODT documents for dolismq
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

require_once __DIR__ . '/modules_controldocument.php';
require_once __DIR__ . '/mod_controldocument_standard.php';
/**
 *	Class to build documents using ODF templates generator
 */
class doc_controldocument_odt extends ModeleODTControlDocument
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public $phpmin = array(5, 5);

	/**
	 * @var string Dolibarr version of the loaded document
	 */
	public $version = 'dolibarr';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = $langs->trans('ControlDocumentDoliSMQTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		// Recupere emetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
	}

	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("errors", "companies"));

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key=>$tmpdir)
		{
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			$tmpdir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if (!is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else
			{
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH))
		{
			$texte .= $langs->trans("DoliSMQNumberOfModelFilesFound").': <b>';
			$texte .= count($listoffiles);
			$texte .= '</b>';
		}

		if ($nbofiles)
		{
			$texte .= '<div id="div_'.get_class($this).'" class="hidden">';
			foreach ($listoffiles as $file)
			{
				$texte .= $file['name'].'<br>';
			}
			$texte .= '</div>';
		}

		$texte .= '</td>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		ControlDocument	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($objectDocument, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $object)
	{
		global $action, $conf, $hookmanager, $langs, $mysoc, $user;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_controldocument_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$hookmanager->initHooks(array('odtgeneration'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}

		$outputlangs->charset_output = 'UTF-8';
		$outputlangs->loadLangs(array("main", "dict", "companies", "dolismq@dolismq"));

		$refModName          = new $conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON($this->db);
		$objectDocumentRef   = $refModName->getNextValue($objectDocument);
		$objectDocument->ref = $objectDocumentRef;
		$objectDocumentID    = $objectDocument->create($user, true, $object);

		$objectDocument->fetch($objectDocumentID);

		$objectref = dol_sanitizeFileName($objectDocument->ref);
		$dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1] . '/controldocument/'. $object->ref;

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/controldocument\//' , $srctemplatepath);
			$filename = preg_replace('/template_/','', $filename[1]);

			$date = dol_print_date(dol_now(),'dayxcard');
			if (preg_match('/_photo/', $filename)) {
				$photo = '_photo';
			} else {
				$photo = '';
			}
			$filename = $objectref . '_' . $date . $photo . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			$objectDocument->last_main_doc = $filename;

			$sql = "UPDATE ".MAIN_DB_PREFIX."dolismq_control";
			$sql .= " SET last_main_doc =" .(!empty($filename) ? "'".$this->db->escape($filename)."'" : 'null');
			$sql .= " WHERE rowid = ".$objectDocument->id;

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$this->db->query($sql);

			$file = $dir.'/'.$filename;

			dol_mkdir($conf->dolismq->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $object);
			// Call the ODTSubstitution hook
			$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$substitutionarray);
			$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH.'odf.php';
			try {
				$odfHandler = new odf(
					$srctemplatepath,
					array(
						'PATH_TO_TMP'	  => $conf->dolismq->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
					)
				);
			}
			catch (Exception $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}

			//Define substitution array
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			//$array_object = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo'] = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			$filearray = dol_dir_list($conf->dolismq->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref . '/thumbs/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
			if (count($filearray)) {
				$image = array_shift($filearray);
				$tmparray['photoDefault'] = $image['fullname'];
			}else {
				$nophoto = '/public/theme/common/nophoto.png';
				$tmparray['photoDefault'] = DOL_DOCUMENT_ROOT.$nophoto;
			}

			$product    = new Product($this->db);
			$productlot = new Productlot($this->db);
			$controldet = new ControlLine($this->db);
			$question   = new Question($this->db);
			$sheet      = new Sheet($this->db);
			$usertmp    = new User($this->db);
			$usertmp2   = new User($this->db);
			$thirdparty = new Societe($this->db);
			$contact    = new Contact($this->db);
			$project    = new Project($this->db);
			$task		= new Task($this->db);

			$object->fetchObjectLinked('', '', '', 'dolismq_control');
			if (!empty($object->linkedObjectsIds['product'])) {
				$product->fetch(array_shift($object->linkedObjectsIds['product']));
			}
			if (!empty($object->linkedObjectsIds['productbatch'])) {
				$productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
			}
			$sheet->fetch($object->fk_sheet);
			$usertmp->fetch($object->fk_user_controller);
			if (!empty($object->linkedObjectsIds['user'])) {
				$usertmp2->fetch(array_shift($object->linkedObjectsIds['user']));
			}
			if (!empty($object->linkedObjectsIds['societe'])) {
				$thirdparty->fetch(array_shift($object->linkedObjectsIds['societe']));
			}
			if (!empty($object->linkedObjectsIds['contact'])) {
				$contact->fetch(array_shift($object->linkedObjectsIds['contact']));
			}
			if (!empty($object->linkedObjectsIds['project'])) {
				$project->fetch(array_shift($object->linkedObjectsIds['project']));
			}
			if (!empty($object->linkedObjectsIds['project_task'])) {
				$task->fetch(array_shift($object->linkedObjectsIds['project_task']));
			}

			$tmparray['mycompany_name']     = $conf->global->MAIN_INFO_SOCIETE_NOM;
			$tmparray['control_ref']        = $object->ref;
			$tmparray['nom']                = $usertmp->lastname . ' '. $usertmp->firstname;
			$tmparray['product_ref']        = $product->ref;
			$tmparray['lot_ref']            = $productlot->batch;
			$tmparray['sheet_ref']          = $sheet->ref;
			$tmparray['sheet_label']        = $sheet->label;
			$tmparray['control_date']       = dol_print_date($object->date_creation, 'dayhour', 'tzuser');
			$tmparray['user_label']         = $usertmp2->lastname . ' '. $usertmp2->firstname;
			$tmparray['thirdparty_label']   = $thirdparty->name;
			$tmparray['contact_label']      = $contact->firstname . ' '. $contact->lastname;
			$tmparray['project_task_ref']   = $project->ref . '-' . $task->ref;
			$tmparray['project_task_label'] = $project->label . '-' . $task->label;

			switch ($object->verdict) {
				case 1:
					$tmparray['verdict'] = 'OK';
					break;
				case 2:
					$tmparray['verdict'] = 'KO';
					break;
				default:
					$tmparray['verdict'] = '';
					break;
			}

			$tmparray['public_note'] = $object->note_public;

			foreach ($tmparray as $key=>$value)
			{
				try {
					if ($key == 'photoDefault' || preg_match('/logo$/', $key)) // Image
					{
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
					}
					else    // Text
					{
						if (empty($value)) {
							$odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
						} else {
							$odfHandler->setVars($key, html_entity_decode($value,ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
						}
					}
				}
				catch (OdfException $e)
				{
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}
			// Replace tags of lines
			try
			{
				$foundtagforlines = 1;
				if ($foundtagforlines) {
					if ( ! empty( $object ) ) {
						$listlines = $odfHandler->setSegment('questions');
						$object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');
						$questionIds = $object->linkedObjectsIds;
						if ( ! empty($questionIds['dolismq_question']) && $questionIds > 0) {
							foreach ($questionIds['dolismq_question'] as $questionId) {
								$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
								if ($result > 0 && is_array($result)) {
									$itemControlDet = array_shift($result);
									$answer = $itemControlDet->answer;
									$comment = $itemControlDet->comment;
								}
								$item = $question;
								$item->fetch($questionId);

								$tmparray['ref']         = $item->ref;
								$tmparray['label']       = $item->label;
								$tmparray['description'] = $item->description;

								if (!empty($conf->global->DOLISMQ_CONTROLDOCUMENT_DISPLAY_MEDIAS)) {
									$path = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $item->ref . '/photo_ok/thumbs/';

									$filearray = dol_dir_list($path, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);

									if (count($filearray)) {
										$image = array_shift($filearray);
										$tmparray['photo_ok'] = $image['fullname'];
									} else {
										$nophoto = '/public/theme/common/nophoto.png';
										$tmparray['photo_ok'] = DOL_DOCUMENT_ROOT . $nophoto;
									}

									$path = $conf->dolismq->multidir_output[$conf->entity] . '/question/' . $item->ref . '/photo_ko/thumbs/';

									$filearray = dol_dir_list($path, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);

									if (count($filearray)) {
										$image = array_shift($filearray);
										$tmparray['photo_ko'] = $image['fullname'];
									} else {
										$nophoto = '/public/theme/common/nophoto.png';
										$tmparray['photo_ko'] = DOL_DOCUMENT_ROOT . $nophoto;
									}
								}

								$tmparray['ref_answer'] = $itemControlDet->ref;

								switch ($answer) {
									case 1:
										$tmparray['answer'] = $langs->trans('OK');
										break;
									case 2:
										$tmparray['answer'] = $langs->trans('KO');
										break;
									case 3:
										$tmparray['answer'] = $langs->trans('Repair');
										break;
									case 4:
										$tmparray['answer'] = $langs->trans('NotApplicable');
										break;
									default:
										$tmparray['answer'] = ' ';
										break;
								}

								$path = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $item->ref;
								$fileList = dol_dir_list($path, 'files');
								$tmparray['photo0'] = ' ';
								$tmparray['photo1'] = ' ';
								$tmparray['photo2'] = ' ';
								if (!empty($fileList)) {
									for ($i = 0; $i <= 2; $i++) {
										if ( $fileList[$i]['level1name'] == $item->ref) {
											$file_small = preg_split('/\./', $fileList[$i]['name']);
											$new_file = $file_small[0] . '_small.' . $file_small[1];
											$image = $path . '/thumbs/' . $new_file;
											$tmparray['photo' . $i] = $image;
										}
									}
								} else {
									$tmparray['photo0'] = ' ';
									$tmparray['photo1'] = ' ';
									$tmparray['photo2'] = ' ';
								}

								$tmparray['comment'] = dol_htmlentitiesbr_decode(strip_tags($comment, '<br>'));

								unset($tmparray['object_fields']);
								unset($tmparray['object_array_options']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
								foreach ($tmparray as $key => $val) {
									try {
										if (file_exists($val)) {
											$listlines->setImage($key, $val);
										} else {
											if (empty($val)) {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											} else {
												$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
											}
										}
									} catch (OdfException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									} catch (SegmentException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									}
								}
								$listlines->merge();
							}
							$odfHandler->mergeSegment($listlines);
						}
					}
				}
			}
			catch (OdfException $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}
			// Replace labels translated
			$tmparray = $outputlangs->get_translations_for_substitutions();

			foreach ($tmparray as $key=>$value)
			{

				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				}
				catch (OdfException $e)
				{
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Write new file
			if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}
			else {
				try {
					$odfHandler->saveToDisk($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}

			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath'=>$file);

			return 1; // Success
		}
		else
		{
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}

		return -1;
	}
}
