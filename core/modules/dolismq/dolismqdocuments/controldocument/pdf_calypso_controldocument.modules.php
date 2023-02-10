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
 *	\file       core/modules/dolismqdocumets/controldocument/pdf_calypso_controldocument.modules.php
 *	\ingroup    dolismq
 *	\brief      Calypso control document template class file
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/modules_controldocument.php';
require_once __DIR__ . '/mod_controldocument_standard.php';

/**
 *	Class to build control documents with model Calypso
 */
class pdf_calypso_controldocument extends ModeleODTControlDocument
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * Recipient
	 * @var Societe
	 */
	public $recipient;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = 'calypso';
		$this->description = $langs->trans('ControlDocumentPDFDoliSMQTemplate');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->orientation = 'L';
		if ($this->orientation == 'L' || $this->orientation == 'Landscape') {
			$this->page_largeur = $formatarray['height'];
			$this->page_hauteur = $formatarray['width'];
		} else {
			$this->page_largeur = $formatarray['width'];
			$this->page_hauteur = $formatarray['height'];
		}

		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 0; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_codeproduitservice = 0; // Display product-service code
		$this->option_multilang = 0; // Available in several languages
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}

		// Define position of columns
		if ($this->orientation == 'L' || $this->orientation == 'Landscape') {
			$this->posxlabelinfo   = $this->marge_gauche + 10;
			$this->posxcontrolinfo = $this->marge_gauche + 50;
			$this->posxnote        = $this->marge_gauche + 30;

		} else {
			$this->posxlabelinfo   = $this->marge_gauche + 10;
			$this->posxcontrolinfo = $this->marge_gauche + 50;
			$this->posxnote        = $this->marge_gauche + 30;
		}

		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxcompanyname -= 30;
			$this->posxcontrolref  -= 30;
			$this->posxname        -= 30;
			$this->posxproductref  -= 30;
			$this->posxlotref      -= 30;
			$this->posxsheetref    -= 30;
			$this->posxsheetlabel  -= 45;
			$this->posxuser        -= 30;
			$this->posxthirdparty  -= 30;
			$this->posxcontact     -= 30;
			$this->posxproject     -= 30;
			$this->posxtask        -= 30;
			$this->posxcontroldate -= 30;
			$this->posxverdict     -= 30;
			$this->posxnote        -= 30;
		}
	}

	/**
	 *  Function to build a document on disk using the generic pdf module.
	 *
	 * 	@param	ControlDocument $objectDocument		Object source to build document
	 * 	@param	Translate		$outputlangs		Lang output object
	 * 	@param 	string 			$srctemplatepath 	Full path of source filename for generator using a template file
	 * 	@param	int				$hidedetails		Do not show line details
	 * 	@param	int				$hidedesc			Do not show desc
	 * 	@param	int				$hideref			Do not show ref
	 * 	@param	Object			$object			    Object to retrieve info from
	 *	@return	int         						1 if OK, <=0 if KO
	 * 	@throws Exception
	 */
	public function write_file($objectDocument, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $object)
	{
		global $conf, $hookmanager, $langs, $user;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "dolismq@dolismq"));

		if ($conf->dolismq->multidir_output) {
			$mod = new $conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON($this->db);
			$ref = $mod->getNextValue($objectDocument);

			$objectref   = dol_sanitizeFileName($ref);
			$documentref = dol_sanitizeFileName($object->ref);

			$dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1] . '/controldocument/' . $documentref;

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				$objectDocument->ref = $ref;
				$id = $objectDocument->create($user, true, $object);
				$objectDocument->fetch($id);

				$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

				$filename = preg_split('/controldocument\//' , $srctemplatepath);
				$filename = preg_replace('/template_/','', $filename[1]);

				$date     = dol_print_date(dol_now(), 'dayxcard');

				$filename = $objectref . '_' . $date . '.pdf';
				$filename = str_replace(' ', '_', $filename);
				$filename = dol_sanitizeFileName($filename);

				$objectDocument->last_main_doc = $filename;

				$sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'dolismq_control';
				$sql .= ' SET last_main_doc =' . (!empty($filename) ? "'" . $this->db->escape($filename) . "'" : 'null');
				$sql .= ' WHERE rowid = ' . $objectDocument->id;

				dol_syslog('admin.lib::Insert last main doc', LOG_DEBUG);
				$this->db->query($sql);

				$file = $dir . '/' . $filename;

				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 40; // Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				// Complete object by loading several other informations
				$extrafields = new ExtraFields($this->db);
				$product     = new Product($this->db);
				$productlot  = new Productlot($this->db);
				$controldet  = new ControlLine($this->db);
				$sheet       = new Sheet($this->db);
				$usertmp     = new User($this->db);
				$usertmp2    = new User($this->db);
				$thirdparty  = new Societe($this->db);
				$contact     = new Contact($this->db);
				$project     = new Project($this->db);
				$task        = new Task($this->db);

				// Fetch control informations
				$object->fetchObjectLinked('', '', '', 'dolismq_control');
				if (!empty($object->linkedObjectsIds['product'])) {
					$product->fetch(array_shift($object->linkedObjectsIds['product']));
				}
				if (!empty($object->linkedObjectsIds['productbatch'])) {
					$productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
				}
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

				$sheet->fetch($object->fk_sheet);
				$usertmp->fetch($object->fk_user_controller);

				// Assert control informations
				$tmparray['SocietyName']      = (!empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? $conf->global->MAIN_INFO_SOCIETE_NOM : $langs->trans('NoData'));
				$tmparray['ControlDocument']  = (!empty($object->ref) ? $object->ref : $langs->trans('NoData'));
				$tmparray['ControlerName']    = (!empty($usertmp->id > 0) ? $usertmp->lastname . ' '. $usertmp->firstname : $langs->trans('NoData'));
				$tmparray['ControledProduct'] = (!empty($product->ref) ? $product->ref : $langs->trans('NoData'));
				$tmparray['LotNumber']        = (!empty($productlot->batch) ? $productlot->batch : $langs->trans('NoData'));
				$tmparray['Sheet']            = (!empty($sheet->ref) ? $sheet->ref . ' ' . $sheet->label : $langs->trans('NoData'));
				$tmparray['ControlDate']      = (!empty($object->date_creation) ? dol_print_date($object->date_creation, 'dayhour', 'tzuser') : $langs->trans('NoData'));
				$tmparray['User']             = (!empty($usertmp2->id > 0) ? $usertmp2->lastname . ' ' . $usertmp2->firstname : $langs->trans('NoData'));
				$tmparray['ThirdParty']       = (!empty($thirdparty->name) ? $thirdparty->name : $langs->trans('NoData'));
				$tmparray['Contact']          = (!empty($contact->id > 0) ? $contact->firstname . ' ' . $contact->lastname : $langs->trans('NoData'));
				$tmparray['Project']          = (!empty($project->id > 0) ? $project->ref . ' - ' . $project->title : $langs->trans('NoData'));
				$tmparray['Task']             = (!empty($task->id > 0) ? $task->ref . ' - ' . $task->label : $langs->trans('NoData'));

				switch ($object->verdict) {
					case 1:
						$tmparray['Verdict'] = 'OK';
						break;
					case 2:
						$tmparray['Verdict'] = 'KO';
						break;
					default:
						$tmparray['Verdict'] = '';
						break;
				}

				$pdf->Open();
				$pagenb = 0;
				$pageBreak = False;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities('ControlDocument'));
				$pdf->SetCreator('Dolibarr ' . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref). ' ' .$outputlangs->transnoentities('ControlDocument'));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				// New page
				$pdf->AddPage($this->orientation);
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 20, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 40; // Set top container space before big container
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 40 : 10);

				// Show control informations
				foreach($tmparray as $key => $value) {
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$value = make_substitutions($value, $substitutionarray, $outputlangs);
					$value = convertBackOfficeMediasLinksToPublicLinks($value);

					// Left key cells must be bold
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->writeHTMLCell(190, 3, $this->posxlabelinfo - 8, $tab_top, dol_htmlentitiesbr($langs->trans($key)), 0, 1);

					// Last key value should be bigger than the others value
					if ($key == array_key_last($tmparray)) {
						$pdf->SetFont('', 'B', $default_font_size + 5);
					} else {
						$pdf->SetFont('', '', $default_font_size);
						$pdf->line($this->marge_gauche, $tab_top + 6, $this->marge_gauche + $this->posxcontrolinfo + $this->posxlabelinfo + 20, $tab_top + 6);
					}
					$pdf->writeHTMLCell(190, 3, $this->posxcontrolinfo - 4, $tab_top, dol_htmlentitiesbr($value), 0, 1);

					$tab_top += 10;
				}
				// Rect takes a length in 3rd parameter
				$pdf->SetDrawColor(120, 120, 120);
				$pdf->Rect($this->marge_gauche, 35, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_top - 35);
				$pdf->line($this->marge_gauche + $this->posxcontrolinfo - $this->posxlabelinfo, 35, $this->marge_gauche + $this->posxcontrolinfo - $this->posxlabelinfo, $tab_top);
				$pdf->line($this->marge_gauche + $this->posxcontrolinfo + $this->posxlabelinfo + 20, 35, $this->marge_gauche + $this->posxcontrolinfo + $this->posxlabelinfo + 20, $tab_top);

				// Display public note and picture
				$tab_top += 5;
				$tmparray['NoteControl'] = $object->note_public;
				if (strlen($tmparray['NoteControl']) >= 1000) {
					$tmparray['NoteControl'] = substr($tmparray['NoteControl'], 0, 1000) . '...';
				}
				$nophoto = '/public/theme/common/nophoto.png';
				$tmparray['DefaultPhoto'] = DOL_DOCUMENT_ROOT.$nophoto;

				$pdf->Image($tmparray['DefaultPhoto'], $this->marge_gauche + $this->posxcontrolinfo + $this->posxlabelinfo + 40, 50, 0, $tab_top - 60);

				if ($pdf->getStringHeight(240, $tmparray['NoteControl']) > 40) {
					$this->_pagefoot($pdf, $object, $outputlangs, 1);
					$pdf->AddPage($this->orientation, '', true);
					$pdf->SetDrawColor(120, 120, 120);
					if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
						$this->_pagehead($pdf, $object, 1, $outputlangs);
					}
					$tab_top = $tab_top_newpage;
				}
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $tab_top, dol_htmlentitiesbr($langs->trans('NoteControl') . ' : '), 0, 1);
				$pdf->writeHTMLCell(240, 3, $this->posxnote, $tab_top, dol_htmlentitiesbr($tmparray['NoteControl']), 0, 1);

				// New page for the incoming table of questions/answer
				$this->_pagefoot($pdf, $object, $outputlangs, 1);
				$pdf->AddPage($this->orientation, '', true);
				$pagenb++;
				if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
					$this->_pagehead($pdf, $object, 1, $outputlangs);
				}

				//Fetch and count the number of questions
				$object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');

				// Info before question/answer table
				$pdf->SetFont('', '', $default_font_size + 2);
				$pdf->writeHTMLCell(200, 3, $this->marge_gauche, $tab_top_newpage, dol_htmlentitiesbr($langs->trans('ControlAndAnswerList')), 0, 1, false, true, 'L');

				$iniY              = 45;
				$curY              = $iniY + 10;
				$curX              = $this->marge_gauche + 7;
				$tableHeaderHeight = $this->marge_haute * 1.5;

				// Draw the first rect at the top of table corresponding to cell informations
				$pdf->SetDrawColor(120, 120, 120);
				$pdf->Rect($this->marge_gauche, $curY, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tableHeaderHeight);

				// Draw informations of the header table
				$pdf->writeHTMLCell(20, 3, $curX, $curY + 2, dol_htmlentitiesbr($langs->trans("QuestionRef")), 0, 1, false, true, "C");
				$curX += 40;
				$pdf->writeHTMLCell(40, 3, $curX, $curY + 2, dol_htmlentitiesbr($langs->trans("QuestionTitle")), 0, 1, false, true, "C");
				$curX += 58;
				$pdf->writeHTMLCell(20, 3, $curX, $curY + 2, dol_htmlentitiesbr($langs->trans("AnswerRef")), 0, 1, false, true, "C");
				$curX += 65;
				$pdf->writeHTMLCell(40, 3, $curX, $curY + 2, dol_htmlentitiesbr($langs->trans("AnswerComment")), 0, 1, false, true, "C");
				$curX += 72;
				$pdf->writeHTMLCell(40, 3, $curX, $curY + 2, dol_htmlentitiesbr($langs->trans("Status")), 0, 1, false, true, "C");
				$curY += $tableHeaderHeight + 2;

				// Loop on each questions
				$tab_height  = 0;
				$nbQuestions = 0;
				foreach($object->linkedObjects['dolismq_question'] as $question) {
					$nbQuestions++;
					$tmpTableArray = array();

					// Question informations
					$tmpTableArray['questionRef']  = $question->ref;
					$tmpTableArray['questionLabel'] = $langs->trans('Title') . ' : ' . $question->label;
					$tmpTableArray['questionDesc'] = $langs->trans('Description') . ' : ' . $question->description;

					// Answer informations
					$result = $controldet->fetchFromParentWithQuestion($object->id, $question->id);
					if ($result > 0 && is_array($result)) {
						$answer = array_shift($result);
						$tmpTableArray['answerRef'] = $answer->ref;
						$tmpTableArray['answerComment'] = (empty($answer->comment) ? 'NoData' :  $langs->trans('Comment') . ' : ' . $answer->comment);
						$answerResult = $answer->answer;

						switch ($answerResult) {
							case 1:
								$tmpTableArray['answerLabel'] = $langs->trans('OK');
								break;
							case 2:
								$tmpTableArray['answerLabel'] = $langs->trans('KO');
								break;
							case 3:
								$tmpTableArray['answerLabel'] = $langs->trans('Repair');
								break;
							case 4:
								$tmpTableArray['answerLabel'] = $langs->trans('NotApplicable');
								break;
							default:
								$tmpTableArray['answerLabel'] = ' ';
								break;
						}
					} else {
						$tmpTableArray['answerRef'] = 'NoData';
						$tmpTableArray['answerComment'] = 'NoData';
						$tmpTableArray['answerLabel'] = 'NoData';
					}

					$path = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $tmpTableArray['questionRef'];
					$fileList = dol_dir_list($path, 'files');
					// Fill an array with photo path and ref of the answer for next loop
					if (is_array($fileList) && !empty($fileList)) {
						foreach ($fileList as $singleFile) {
							$file_small = preg_split('/\./', $singleFile['name']);
							$new_file = $file_small[0] . '_small.' . $file_small[1];
							$image = $path . '/thumbs/' . $new_file;
							$photoArray[$image] = $tmpTableArray['answerRef'];
						}
					}

					$pdf->startTransaction();

					$addY      = (strlen($tmpTableArray['questionDesc']) >= strlen($tmpTableArray['answerComment'])) ? $pdf->getStringHeight(55, $tmpTableArray['questionDesc']) : $pdf->getStringHeight(105, $tmpTableArray['answerComment']);
					if ($addY < 20) {
						$addY += 10;
					}
					$pageBreak = ($curY + $addY >= $this->page_hauteur - $this->marge_basse) ? True : False;

					// If we are at the end of the page, create a new page a create a new top table
					if ($pageBreak == True) {
						if ($pagenb == 2) {
							$this->_tableau($pdf, $tableHeaderHeight + $iniY + 10, $tab_height, 2, $outputlangs);
						}

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pdf->AddPage($this->orientation, '', true);
						$pagenb++;
						$pdf->SetDrawColor(120, 120, 120);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 1, $outputlangs);
						}
						if ($pagenb > 2) {
							$this->_tableau($pdf, $tab_top_newpage - 5, $this->page_hauteur - $tab_top_newpage - $this->marge_basse, 0, $outputlangs);
						}

						$curY       = $tab_top_newpage - 3;
						$tab_height = 0;
						$pageBreak  = false;
					}
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor(0, 0, 0);
					$curX = $this->marge_gauche + 5;

					// Question ref
					$pdf->writeHTMLCell(40, 3, $curX, $curY, dol_htmlentitiesbr($langs->trans($tmpTableArray['questionRef'])), 0, 1, false, true, "L");
					$curX += 33;

					// Question label
					$pdf->writeHTMLCell(55, 3, $curX, $curY, dol_htmlentitiesbr($langs->trans($tmpTableArray['questionLabel'])), 0, 1, false, true, "L");
					$curY += 8;

					// Question description
					$pdf->writeHTMLCell(55, 3, $curX, $curY, dol_htmlentitiesbr($langs->trans($tmpTableArray['questionDesc'])), 0, 1, false, true, "L");
					$curX += 65;
					$curY -= 8;

					// Answer ref
					$pdf->writeHTMLCell(40, 3, $curX, $curY, dol_htmlentitiesbr($langs->trans($tmpTableArray['answerRef'])), 0, 1, false, true, "L");
					$curX += 35;

					// Answer comment
					$pdf->writeHTMLCell(105, 3, $curX, $curY, dol_htmlentitiesbr($langs->trans($tmpTableArray['answerComment'])), 0, 1, false, true, "L");
					$curX += 110;

					// Status
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->writeHTMLCell(25, 3, $curX, $curY + ($addY / 2) - 4, dol_htmlentitiesbr($langs->trans($tmpTableArray['answerLabel'])), 0, 1, false, true, "C");

					// Draw line if no page break (else line is drawn by table)
					$curY += $addY;
					if ($pageBreak == False) {
						$pdf->line($this->marge_gauche, $curY, $this->page_largeur - $this->marge_gauche, $curY);
					}
					$curY       += 2;
					$tab_height += $addY + 2;
				}
				if ($pagenb == 2) {
					$this->_tableau($pdf, $tableHeaderHeight + $iniY + 10, $this->page_hauteur - $tableHeaderHeight - $tab_top_newpage - $iniY + 10, 2, $outputlangs);
				}
				$this->_pagefoot($pdf, $object, $outputlangs, 1);

				if (!empty($photoArray) && is_array($photoArray)) {
					$pdf->AddPage($this->orientation, '', true);
					$pagenb++;
					if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
						$this->_pagehead($pdf, $object, 1, $outputlangs);
					}
					$pdf->SetDrawColor(120, 120, 120);

					$curY = $tab_top_newpage;
					$previousRef = '';
					foreach ($photoArray as $path => $ref) {
						if ($ref != $previousRef) {
							$pdf->writeHTMLCell(40, 3, $this->marge_gauche, $curY, dol_htmlentitiesbr($langs->trans($ref) . ' : '), 0, 1, false, true, "L");
							$curY += 10;
						}
						if (is_readable($path)) {
							list($width, $height) = getimagesize($path);
							$pdf->Image($path, $this->marge_gauche, $curY, 0, 0, '', '', '', false, 300, 'C'); // width=0 (auto)
							$curY += $height;
						} else {
							$pdf->SetTextColor(200, 0, 0);
							$pdf->SetFont('', 'B', $default_font_size - 2);
							$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorLogoFileNotFound', $path), 0, 'L');
							$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorGoToModuleSetup'), 0, 'L');
							$curY += 10;
						}
						$previousRef = $ref;

						if ($curY >= $this->page_hauteur - $this->marge_basse && $path != array_key_last($photoArray)) {
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							$pdf->AddPage($this->orientation, '', true);
							$pagenb++;
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
								$this->_pagehead($pdf, $object, 1, $outputlangs);
							}
							$curY = $tab_top_newpage;
							$pdf->SetDrawColor(120, 120, 120);
						}
					}
					$this->_pagefoot($pdf, $object, $outputlangs, 1);
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			} else {
				$this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities('ErrorConstantNotDefined', 'DOLISMQ_OUTPUTDIR');
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$pagenb			Page number
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $pagenb = 0, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		$pdf->SetDrawColor(128, 128, 128);

		// Draw rect of all tab (title + lines). Rect takes a length in 3rd parameter
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);

		if ($pagenb == 2) {
			$tab_top = 55;
			$curY = $tab_top + $tab_height + $this->marge_haute * 1.5;
		} else {
			$curY = $this->page_hauteur - $this->marge_basse - 5;
		}

		// Line takes a position y in 3rd parameter
		$curX = $this->marge_gauche;
		$curX += 35;
		$pdf->line($curX, $tab_top, $curX, $curY);
		$curX += 60;
		$pdf->line($curX, $tab_top, $curX, $curY);
		$curX += 40;
		$pdf->line($curX, $tab_top, $curX, $curY);
		$curX += 110;
		$pdf->line($curX, $tab_top, $curX, $curY);

		//$pdf->SetTextColor(0, 0, 0);
		//$pdf->SetFont('', '', $default_font_size);

		//$pdf->SetXY($this->posxref, $tab_top + 1);
		//$pdf->MultiCell($this->posxrisk - $this->posxref, 3, $outputlangs->transnoentities('Tasks'), '', 'L');

	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Project		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx = $this->page_largeur - $this->marge_droite - 100;
		$posy = $this->marge_haute;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorLogoFileNotFound', $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorGoToModuleSetup'), 0, 'L');
			}
		} else {
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');
		}

		// CreativeCommons by-sa
		$creativeCommons = DOL_DOCUMENT_ROOT . '/custom/dolismq/img/creativecommons/cc-by-sa_icon.png';
		if (is_readable($creativeCommons)) {
			$height = pdf_getHeightForLogo($creativeCommons);
			$pdf->Image($creativeCommons, $this->page_largeur / 2 - $height, $posy, 22, 0); //height = 0 (auto)
		} else {
			$pdf->SetTextColor(200, 0, 0);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorLogoFileNotFound', $creativeCommons), 0, 'L');
			$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorGoToModuleSetup'), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities('Control'). ' ' .$outputlangs->convToOutputCharset($object->ref), '', 'R');
		$pdf->SetFont('', '', $default_font_size + 2);

		$posy += 6;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities('ControlDate'). ' : ' .dol_print_date($object->date_creation, 'day', false, $outputlangs, true), '', 'R');

		if (is_object($object->thirdparty)) {
			$posy += 6;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities('ThirdParty'). ' : ' .$object->thirdparty->getFullName($outputlangs), '', 'R');
		}

		$pdf->SetTextColor(0, 0, 60);

		// Add list of linked objects
		/* Removed: A project can have more than thousands linked objects (orders, invoices, proposals, etc....
		$object->fetchObjectLinked();

		foreach($object->linkedObjects as $objecttype => $objects)
		{
			var_dump($objects);exit;
			if ($objecttype == 'commande')
			{
				$outputlangs->load('orders');
				$num=count($objects);
				for ($i=0;$i<$num;$i++)
				{
					$posy+=4;
					$pdf->SetXY($posx,$posy);
					$pdf->SetFont('','', $default_font_size - 1);
					$text=$objects[$i]->ref;
					if ($objects[$i]->ref_client) $text.=' ('.$objects[$i]->ref_client.')';
					$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
				}
			}
		}
		*/
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf     			PDF
	 *  @param	Project		$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return    int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'PROJECT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
