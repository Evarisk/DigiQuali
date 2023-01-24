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

		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];

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
			$this->posxref  = $this->marge_gauche + 1;
			$this->posxdesc = $this->marge_gauche + 25;
			$this->posxtest = $this->marge_gauche + 25;
		} else {
			$this->posxref  = $this->marge_gauche + 1;
			$this->posxdesc = $this->marge_gauche + 25;
			$this->posxtest = $this->marge_gauche + 25;
		}

		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxref  -= 20;
			$this->posxdesc -= 20;
			$this->posxtest -= 20;
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

			$objectref = dol_sanitizeFileName($object->ref);

			$dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1] . '/controldocument/' . $objectref;

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
				$extrafields    = new ExtraFields($this->db);
				empty($object->lines) ? $nblines = 3 : $nblines = count($object->lines);

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities('ControlDocument'));
				$pdf->SetCreator('Dolibarr ' . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref). ' ' .$outputlangs->transnoentities('ControlDocument'));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				//$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

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

				$tab_top = 50; // Set top container space before big container
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				// Show public note
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if ($notetoshow) {
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$tab_top -= 2;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxref - 1, $tab_top - 2, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 2, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 2);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				$heightoftitleline = 10;
				$iniY = $tab_top + $heightoftitleline + 1;
				$curY = $tab_top + $heightoftitleline + 1;
				$nexY = $tab_top + $heightoftitleline + 1;

				// Loop on each lines

				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin(100);
					$pdf->setPageOrientation($this->orientation, 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					// Description of line
					$ref  = $object->ref;
					$test = "test";
					$desc = $this->description;

					$showpricebeforepagebreak = 1;

					$pdf->startTransaction();

					// Ref
					$pdf->SetXY($this->posxref, $curY);
					$pdf->MultiCell($this->posxref - $this->posxdesc, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');

					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

						// Ref
						$pdf->SetXY($this->posxref, $curY);
						$posybefore = $pdf->GetY();
						$pdf->MultiCell($this->posxref - $this->posxdesc, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');

						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage($this->orientation, '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break

							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}

							$forcedesconsamepage = 1;
							if ($forcedesconsamepage) {
								$pdf->rollbackTransaction(true);
								$pageposafter = $pageposbefore;
								$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
								$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut
								$pdf->MultiCell(0, 3, ''); // Set interline to 3
								$pdf->SetTextColor(0, 0, 0);

								$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
								$curY = $tab_top_newpage + $heightoftitleline + 1;

								// Label
								$pdf->SetXY($this->posxtest, $curY);
								$posybefore = $pdf->GetY();
								$pdf->MultiCell($this->posxtest, 3, $outputlangs->convToOutputCharset($test), 0, 'L');

								// Description
								$pdf->SetXY($this->posxdesc, $curY);
								$posybefore = $pdf->GetY();
								$pdf->MultiCell($this->posxdesc, 3, $outputlangs->convToOutputCharset($desc), 0, 'R');

								$pageposafter = $pdf->getPage();
								$posyafter = $pdf->GetY();
							}
						}
						//var_dump($i.' '.$posybefore.' '.$posyafter.' '.($this->page_hauteur -  ($heightforfooter + $heightforfreetext + $heightforinfotot)).' '.$showpricebeforepagebreak);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription = $pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation($this->orientation, 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						//var_dump($pageposbefore.'-'.$pageposafter.'-'.$showpricebeforepagebreak);
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage + $heightoftitleline + 1;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// ----- List of element to print in the main container of the pdf doc ----- //

					// Ref of task
					//$pdf->SetXY($this->posxref, $curY);
					//$pdf->MultiCell($this->posxref - $this->posxdesc, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');

					// Desk of task
					$pdf->SetXY($this->posxdesc, $curY);
					$pdf->MultiCell($this->posxref - $this->posxdesc, 3, $outputlangs->convToOutputCharset($desc), 0, 'R');

					// Label of task
					$pdf->SetXY($this->posxtest, $curY);
					$pdf->MultiCell($this->posxref - $this->posxtest, 3, $outputlangs->convToOutputCharset($test), 0, 'L');

					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY += 2; // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage($this->orientation);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}
				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Footer of the page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
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
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf, $mysoc;

		$heightoftitleline = 10;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128, 128, 128);

		// Draw rect of all tab (title + lines). Rect takes a length in 3rd parameter
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);

		// Line takes a position y in 3rd parameter
		$pdf->line($this->marge_gauche, $tab_top + $heightoftitleline, $this->page_largeur - $this->marge_droite, $tab_top + $heightoftitleline);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size);

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
