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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/dolismq/dolismqdocuments/controldocument/doc_controldocument_odt.modules.php
 * \ingroup dolismq
 * \brief   File of class to build ODT control document.
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';

// Load DoliSMQ libraries.
require_once __DIR__ . '/modules_controldocument.php';
require_once __DIR__ . '/mod_controldocument_standard.php';

require_once __DIR__ . '/../../../../../class/question.class.php';
require_once __DIR__ . '/../../../../../class/sheet.class.php';
require_once __DIR__ . '/../../../../../class/answer.class.php';

/**
 * Class to build documents using ODF templates generator.
 */
class doc_controldocument_odt extends ModeleODTControlDocument
{
    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public array $phpmin = [7, 4];

    /**
     * @var string Dolibarr version of the loaded document.
     */
    public string $version = 'dolibarr';

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        global $langs;

        // Load translation files required by the page
        $langs->loadLangs(['main', 'companies']);

        $this->db          = $db;
        $this->name        = $langs->trans('ODTDefaultTemplateName');
        $this->description = $langs->trans('DocumentModelOdt');
        $this->scandir     = 'DOLISMQ_CONTROLDOCUMENT_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan.

        // Page size for A4 format.
        $this->type         = 'odt';
        $this->page_largeur = 0;
        $this->page_hauteur = 0;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 0;
        $this->marge_droite = 0;
        $this->marge_haute  = 0;
        $this->marge_basse  = 0;

        $this->option_logo      = 1; // Display logo.
        $this->option_multilang = 1; // Available in several languages.
    }

    /**
     * Return description of a module.
     *
     * @param  Translate $langs Lang object to use for output.
     * @return string           Description.
     */
    public function info(Translate $langs): string
    {
        global $conf, $langs;

        // Load translation files required by the page.
        $langs->loadLangs(['errors', 'companies']);

        $texte = $this->description . ' . <br>';
        $texte .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . newToken() . '">';
        $texte .= '<input type="hidden" name="action" value="setModuleOptions">';
        $texte .= '<input type="hidden" name="param1" value="DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH">';
        $texte .= '<table class="nobordernopadding centpercent">';

        // List of directories area.
        $texte .= '<tr><td>';
        $texttitle   = $langs->trans('ListOfDirectories');
        $listofdir   = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH)));
        $listoffiles = [];
        foreach ($listofdir as $key=>$tmpdir) {
            $tmpdir = trim($tmpdir);
            $tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
            $tmpdir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpdir);
            if (!$tmpdir) {
                unset($listofdir[$key]);
                continue;
            }
            if (!is_dir($tmpdir)) {
                $texttitle .= img_warning($langs->trans('ErrorDirNotFound', $tmpdir), 0);
            } else {
                $tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
                if (count($tmpfiles)) {
                    $listoffiles = array_merge($listoffiles, $tmpfiles);
                }
            }
        }

        // Scan directories.
        $nbFiles = count($listoffiles);
        if (!empty($conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON_ODT_PATH)) {
            $texte .= $langs->trans('NumberOfModelFilesFound') . ': <b>';
            $texte .= count($listoffiles);
            $texte .= '</b>';
        }

        if ($nbFiles) {
            $texte .= '<div id="div_' . get_class($this) . '" class="hidden">';
            foreach ($listoffiles as $file) {
                $texte .= $file['name'] . '<br>';
            }
            $texte .= '</div>';
        }

        $texte .= '</td>';
        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }

    /**
     * Function to build a document on disk using the generic odt module.
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document.
     * @param  Translate        $outputlangs     Lang output object.
     * @param  string           $srctemplatepath Full path of source filename for generator using a template file.
     * @param  int              $hidedetails     Do not show line details.
     * @param  int              $hidedesc        Do not show desc.
     * @param  int              $hideref         Do not show ref.
     * @param  array            $moreparam       More param (Object/user/etc).
     * @return int                               1 if OK, <=0 if KO.
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputlangs, string $srctemplatepath, int $hidedetails = 0, int $hidedesc = 0, int $hideref = 0, array $moreparam)
    {
        global $action, $conf, $hookmanager, $langs, $mysoc;

        $object = $moreparam['object'];

        if (empty($srctemplatepath)) {
            dol_syslog('doc_controldocument_odt::write_file parameter srctemplatepath empty', LOG_WARNING);
            return -1;
        }

        // Add odtgeneration hook.
        if (!is_object($hookmanager)) {
            include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
            $hookmanager = new HookManager($this->db);
        }
        $hookmanager->initHooks(['odtgeneration']);

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        $outputlangs->charset_output = 'UTF-8';
        $outputlangs->loadLangs(['main', 'dict', 'companies', 'dolismq@dolismq', 'products', 'projects', 'bills', 'orders', 'contracts']);

        if ($conf->dolismq->dir_output) {
            $refModName          = new $conf->global->DOLISMQ_CONTROLDOCUMENT_ADDON($this->db);
            $objectDocumentRef   = $refModName->getNextValue($objectDocument);
            $objectDocument->ref = $objectDocumentRef;
            $objectDocumentID    = $objectDocument->create($moreparam['user'], true, $object);

            $objectDocument->fetch($objectDocumentID);

            $objectDocumentRef = dol_sanitizeFileName($objectDocument->ref);

            $dir = $conf->dolismq->multidir_output[$object->entity ?? 1] . '/' . $object->element . 'document/' . $object->ref;
            if ($moreparam['specimen'] == 1 && $moreparam['zone'] == 'public') {
                $dir .= '/public_specimen';
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
                    return -1;
                }
            }

            if (file_exists($dir)) {
                $newFile     = basename($srctemplatepath);
                $newFileTmp  = preg_replace('/\.od(t|s)/i', '', $newFile);
                $newFileTmp  = preg_replace('/template_/i', '', $newFileTmp);
                $societyName = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

                $date = dol_print_date(dol_now(), 'dayxcard');
                $newFileTmp = $date . '_' . $object->ref . '_' . $objectDocumentRef .'_' . $langs->transnoentities($newFileTmp) . '_' . $societyName;
                if ($moreparam['specimen'] == 1) {
                    $newFileTmp .= '_specimen';
                }
                $isPhotoDocument = false;
                if (preg_match('/_photo/', $newFile)) {
                    $isPhotoDocument = true;
                    $newFileTmp .= '_photo';
                }
                $newFileTmp = str_replace(' ', '_', $newFileTmp);

                // Get extension (ods or odt).
                $newFileFormat = substr($newFile, strrpos($newFile, '.') + 1);
                $filename      = $newFileTmp . '.' . $newFileFormat;
                $file          = $dir . '/' . $filename;

                $objectDocument->last_main_doc = $filename;

                $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'dolismq_dolismqdocuments';
                $sql .= ' SET last_main_doc =' . (!empty($objectDocument->last_main_doc) ? "'" . $this->db->escape($objectDocument->last_main_doc) . "'" : 'null');
                $sql .= ' WHERE rowid = ' . $objectDocument->id;

                dol_syslog('dolismq_dolismqdocuments::Insert last main doc', LOG_DEBUG);
                $this->db->query($sql);

                dol_mkdir($conf->dolismq->dir_temp);

                if (!is_writable($conf->dolismq->dir_temp)) {
                    $this->error = 'Failed to write in temp directory ' . $conf->dolismq->dir_temp;
                    dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
                    return -1;
                }

                // Make substitution.
                $substitutionarray = [];
                complete_substitutions_array($substitutionarray, $langs, $object);
                // Call the ODTSubstitution hook.
                $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray];
                $reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.

                // Open and load template.
                require_once ODTPHP_PATH . 'odf.php';
                try {
                    $odfHandler = new odf(
                        $srctemplatepath,
                        [
                            'PATH_TO_TMP'     => $conf->dolismq->dir_temp,
                            'ZIP_PROXY'       => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
                            'DELIMITER_LEFT'  => '{',
                            'DELIMITER_RIGHT' => '}'
                        ]
                    );
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($e->getMessage());
                    return -1;
                }

                // Define substitution array.
                $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                $arraySoc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
                $arraySoc['mycompany_logo'] = preg_replace('/_small/', '_mini', $arraySoc['mycompany_logo']);

                $tmparray = array_merge($substitutionarray, $arraySoc);
                complete_substitutions_array($tmparray, $outputlangs, $object);

                if (!empty($object->photo)) {
                    $path      = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/photos';
                    $fileSmall = saturne_get_thumb_name($object->photo);
                    $image     = $path . '/thumbs/' . $fileSmall;
                    $tmparray['photoDefault'] = $image;
                } else {
                    $noPhoto                  = '/public/theme/common/nophoto.png';
                    $tmparray['photoDefault'] = DOL_DOCUMENT_ROOT . $noPhoto;
                }

                $controldet = new ControlLine($this->db);
                $question   = new Question($this->db);
                $sheet      = new Sheet($this->db);
                $answer     = new Answer($this->db);
                $signatory  = new SaturneSignature($this->db);
                $usertmp    = new User($this->db);
                $projecttmp = new Project($this->db);

                $sheet->fetch($object->fk_sheet);
                $usertmp->fetch($object->fk_user_controller);
                $projecttmp->fetch($object->projectid);

                $object->fetchObjectLinked('', '', '', 'dolismq_control');
                if (!empty($object->linkedObjectsIds['product'])) {
                    $product = new Product($this->db);
                    $product->fetch(array_shift($object->linkedObjectsIds['product']));
                    $tmparray['object_label_ref'] .= (!empty($product->ref) ? $langs->transnoentities('Product') . ' : ' . $product->ref . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['productbatch'])) {
                    $productlot = new Productlot($this->db);
                    $productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
                    $tmparray['object_label_ref'] .= (!empty($productlot->batch) ? $langs->transnoentities('Batch') . ' : ' . $productlot->batch . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['user'])) {
                    $usertmp2 = new User($this->db);
                    $usertmp2->fetch(array_shift($object->linkedObjectsIds['user']));
                    $tmparray['object_label_ref'] .= (!empty($usertmp2->id) ? $langs->transnoentities('User') . ' : ' . strtoupper($usertmp2->lastname) . ' ' . $usertmp2->firstname . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['societe'])) {
                    $thirdparty = new Societe($this->db);
                    $thirdparty->fetch(array_shift($object->linkedObjectsIds['societe']));
                    $tmparray['object_label_ref'] .= (!empty($thirdparty->name) ? $langs->transnoentities('ThirdParty') . ' : ' . $thirdparty->name . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['contact'])) {
                    $contact = new Contact($this->db);
                    $contact->fetch(array_shift($object->linkedObjectsIds['contact']));
                    $tmparray['object_label_ref'] .= (!empty($contact->id) ? $langs->transnoentities('Contact') . ' : ' . strtoupper($contact->lastname) . ' ' . $contact->firstname . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['project'])) {
                    $project = new Project($this->db);
                    $project->fetch(array_shift($object->linkedObjectsIds['project']));
                    $tmparray['object_label_ref'] .= (!empty($project->ref) ? $langs->transnoentities('Project') . ' : ' . $project->ref . ' - ' . $project->title . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['project_task'])) {
                    $task = new Task($this->db);
                    $task->fetch(array_shift($object->linkedObjectsIds['project_task']));
                    $tmparray['object_label_ref'] .= (!empty($task->id) ? $langs->transnoentities('Task') . ' : ' . $task->ref . ' - ' . $task->label . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['facture'])) {
                    $invoice = new Facture($this->db);
                    $invoice->fetch(array_shift($object->linkedObjectsIds['facture']));
                    $tmparray['object_label_ref'] .= (!empty($invoice->id) ? $langs->transnoentities('Bill') . ' : ' . $invoice->ref . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['commande'])) {
                    $order = new Commande($this->db);
                    $order->fetch(array_shift($object->linkedObjectsIds['commande']));
                    $tmparray['object_label_ref'] .= (!empty($order->id) ? $langs->transnoentities('Order') . ' : ' . $order->ref . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['contrat'])) {
                    $contract = new Contrat($this->db);
                    $contract->fetch(array_shift($object->linkedObjectsIds['contrat']));
                    $tmparray['object_label_ref'] .= (!empty($contract->id) ? $langs->transnoentities('Contract') . ' : ' . $contract->ref . chr(0x0A) : '');
                }
                if (!empty($object->linkedObjectsIds['ticket'])) {
                    $ticket = new Ticket($this->db);
                    $ticket->fetch(array_shift($object->linkedObjectsIds['ticket']));
                    $tmparray['object_label_ref'] .= (!empty($ticket->id) ? $langs->transnoentities('Ticket') . ' : ' . $ticket->ref . chr(0x0A) : '');
                }

                $tmparray['control_ref']      = $object->ref;
                $tmparray['object_label_ref'] = rtrim($tmparray['object_label_ref'], chr(0x0A));
                $tmparray['control_date']     = dol_print_date($object->date_creation, 'dayhour', 'tzuser');
                $tmparray['project_label']    = $projecttmp->ref . ' - ' . $projecttmp->title;
                $tmparray['sheet_ref']        = $sheet->ref;
                $tmparray['sheet_label']      = $sheet->label;

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

                $tmparray['mycompany_name']    = $conf->global->MAIN_INFO_SOCIETE_NOM;
                $tmparray['mycompany_address'] = (!empty($conf->global->MAIN_INFO_SOCIETE_ADRESS) ? ' - ' . $conf->global->MAIN_INFO_SOCIETE_ADRESS : '');
                $tmparray['mycompany_website'] = (!empty($conf->global->MAIN_INFO_SOCIETE_WEBSITE) ? ' - ' . $conf->global->MAIN_INFO_SOCIETE_WEBSITE : '');
                $tmparray['mycompany_mail']    = (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL) ? ' - ' . $conf->global->MAIN_INFO_SOCIETE_MAIL : '');
                $tmparray['mycompany_phone']   = (!empty($conf->global->MAIN_INFO_SOCIETE_PHONE) ? ' - ' . $conf->global->MAIN_INFO_SOCIETE_PHONE : '');

                $tempDir = $conf->dolismq->multidir_output[$object->entity ?? 1] . '/temp/';

                foreach ($tmparray as $key => $value) {
                    try {
                        if ($key == 'photoDefault' || preg_match('/logo$/', $key)) {
                            // Image.
                            if (file_exists($value)) {
                                $odfHandler->setImage($key, $value);
                            }else {
                                $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
                            }
                        } elseif (empty($value)) { // Text.
                                $odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                        } else {
                            $odfHandler->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                        }
                    } catch (OdfException $e) {
                        dol_syslog($e->getMessage());
                    }
                }

                // Replace tags of lines.
                try {
                    // Get attendants role controller.
                    $foundtagforlines = 1;
                    try {
                        $listlines = $odfHandler->setSegment('controllers');
                    } catch (OdfException $e) {
                        // We may arrive here if tags for lines not present into template.
                        $foundtagforlines = 0;
                        $listlines = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundtagforlines) {
                        if (!empty($object)) {
                            $signatoriesArray = $signatory->fetchSignatory('Controller', $object->id, $object->element);
                            if (!empty($signatoriesArray) && is_array($signatoriesArray)) {
                                foreach ($signatoriesArray as $objectSignatory) {
                                    $tmparray['controller_fullname'] = strtoupper($objectSignatory->lastname) . ' ' . $objectSignatory->firstname;
                                    switch ($objectSignatory->attendance) {
                                        case 1:
                                            $attendance = $langs->trans('Delay');
                                            break;
                                        case 2:
                                            $attendance = $langs->trans('Absent');
                                            break;
                                        default:
                                            $attendance = $langs->transnoentities('Present');
                                            break;
                                    }
                                    $tmparray['controller_signature_date'] = dol_print_date($objectSignatory->signature_date, 'dayhour', 'tzuser');
                                    $tmparray['controller_attendance'] = $attendance;
                                    if (dol_strlen($objectSignatory->signature) > 0 && $objectSignatory->signature != $langs->transnoentities('FileGenerated')) {
                                        if ($moreparam['specimen'] == 0 || ($moreparam['specimen'] == 1 && $conf->global->DOLISMQ_SHOW_SIGNATURE_SPECIMEN == 1)) {
                                            $encodedImage = explode(',', $objectSignatory->signature)[1];
                                            $decodedImage = base64_decode($encodedImage);
                                            file_put_contents($tempDir . 'signature' . $objectSignatory->id . '.png', $decodedImage);
                                            $tmparray['controller_signature'] = $tempDir . 'signature' . $objectSignatory->id . '.png';
                                        } else {
                                            $tmparray['controller_signature'] = '';
                                        }
                                    } else {
                                        $tmparray['controller_signature'] = '';
                                    }
                                    foreach ($tmparray as $key => $value) {
                                        try {
                                            if ($key == 'controller_signature' && is_file($value)) { // Image
                                                $list = getimagesize($value);
                                                $newWidth = 200;
                                                if ($list[0]) {
                                                    $ratio = $newWidth / $list[0];
                                                    $newHeight = $ratio * $list[1];
                                                    dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
                                                }
                                                $listlines->setImage($key, $value);
                                            } elseif (empty($value)) {
                                                $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                            } elseif (!is_array($value)) {
                                                $listlines->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                            }
                                        } catch (OdfException $e) {
                                            dol_syslog($e->getMessage());
                                        }
                                    }
                                    $listlines->merge();
                                    dol_delete_file($tempDir . 'signature' . $objectSignatory->id . '.png');
                                }
                            } else {
                                $tmparray['controller_fullname'] = '';
                                $tmparray['controller_signature_date'] = '';
                                $tmparray['controller_attendance'] = '';
                                $tmparray['controller_signature'] = '';
                                foreach ($tmparray as $key => $val) {
                                    try {
                                        if (empty($val)) {
                                            $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                        } else {
                                            $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                        }
                                    } catch (SegmentException $e) {
                                        dol_syslog($e->getMessage());
                                    }
                                }
                                $listlines->merge();
                            }
                            $odfHandler->mergeSegment($listlines);
                        }
                    }

                    // Get attendants.
                    $foundtagforlines = 1;
                    try {
                        $listlines = $odfHandler->setSegment('attendants');
                    } catch (OdfException $e) {
                        // We may arrive here if tags for lines not present into template.
                        $foundtagforlines = 0;
                        $listlines = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundtagforlines) {
                        if (!empty($object)) {
                            $signatoriesArray = $signatory->fetchSignatories($object->id, $object->element);
                            if (!empty($signatoriesArray) && is_array($signatoriesArray)) {
                                foreach ($signatoriesArray as $objectSignatory) {
                                    if ($objectSignatory->role != 'Controller') {
                                        $tmparray['attendant_lastname']  = strtoupper($objectSignatory->lastname);
                                        $tmparray['attendant_firstname'] = $objectSignatory->firstname;
                                        switch ($objectSignatory->attendance) {
                                            case 1:
                                                $attendance = $langs->trans('Delay');
                                                break;
                                            case 2:
                                                $attendance = $langs->trans('Absent');
                                                break;
                                            default:
                                                $attendance = $langs->transnoentities('Present');
                                                break;
                                        }
                                        $tmparray['attendant_role']           = $langs->transnoentities($objectSignatory->role);
                                        $tmparray['attendant_signature_date'] = dol_print_date($objectSignatory->signature_date, 'dayhour', 'tzuser');
                                        $tmparray['attendant_attendance']     = $attendance;
                                        if (dol_strlen($objectSignatory->signature) > 0 && $objectSignatory->signature != $langs->transnoentities('FileGenerated')) {
                                            if ($moreparam['specimen'] == 0 || ($moreparam['specimen'] == 1 && $conf->global->DOLISMQ_SHOW_SIGNATURE_SPECIMEN == 1)) {
                                                $encodedImage = explode(',', $objectSignatory->signature)[1];
                                                $decodedImage = base64_decode($encodedImage);
                                                file_put_contents($tempDir . 'signature' . $objectSignatory->id . '.png', $decodedImage);
                                                $tmparray['attendant_signature'] = $tempDir . 'signature' . $objectSignatory->id . '.png';
                                            } else {
                                                $tmparray['attendant_signature'] = '';
                                            }
                                        } else {
                                            $tmparray['attendant_signature'] = '';
                                        }
                                        foreach ($tmparray as $key => $value) {
                                            try {
                                                if ($key == 'attendant_signature' && is_file($value)) { // Image
                                                    $list = getimagesize($value);
                                                    $newWidth = 200;
                                                    if ($list[0]) {
                                                        $ratio     = $newWidth / $list[0];
                                                        $newHeight = $ratio * $list[1];
                                                        dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
                                                    }
                                                    $listlines->setImage($key, $value);
                                                } elseif (empty($value)) {
                                                    $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                                } elseif (!is_array($value)) {
                                                    $listlines->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                                }
                                            } catch (OdfException $e) {
                                                dol_syslog($e->getMessage());
                                            }
                                        }
                                        $listlines->merge();
                                        dol_delete_file($tempDir . 'signature' . $objectSignatory->id . '.png');
                                    }
                                }
                            } else {
                                $tmparray['attendant_lastname']       = '';
                                $tmparray['attendant_firstname']      = '';
                                $tmparray['attendant_role']           = '';
                                $tmparray['attendant_signature_date'] = '';
                                $tmparray['attendant_attendance']     = '';
                                $tmparray['attendant_signature']      = '';
                                foreach ($tmparray as $key => $val) {
                                    try {
                                        if (empty($val)) {
                                            $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                        } else {
                                            $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                        }
                                    } catch (SegmentException $e) {
                                        dol_syslog($e->getMessage());
                                    }
                                }
                                $listlines->merge();
                            }
                            $odfHandler->mergeSegment($listlines);
                        }
                    }

                    // Get questions.
                    $photoArray       = [];
                    $foundtagforlines = 1;
                    try {
                        $listlines = $odfHandler->setSegment('questions');
                    } catch (OdfException $e) {
                        // We may arrive here if tags for lines not present into template.
                        $foundtagforlines = 0;
                        $listlines = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundtagforlines) {
                        if (!empty($object)) {
                            $object->fetchObjectLinked($object->fk_sheet, 'dolismq_sheet');
                            $questionIds = $object->linkedObjectsIds;
                            if (is_array($questionIds['dolismq_question']) && !empty($questionIds['dolismq_question'])) {
                                foreach ($questionIds['dolismq_question'] as $questionId) {
                                    $question->fetch($questionId);

                                    $controldets = $controldet->fetchFromParentWithQuestion($object->id, $questionId);

                                    $tmparray['ref']         = $question->ref;
                                    $tmparray['label']       = $question->label;
                                    $tmparray['description'] = $question->description;

                                    if (is_array($controldets) && !empty($controldets)) {
                                        $questionAnswerLine     = array_shift($controldets);
                                        $tmparray['ref_answer'] = $questionAnswerLine->ref;
                                        $tmparray['comment']    = dol_htmlentitiesbr_decode(strip_tags($questionAnswerLine->comment, '<br>'));

                                        $answerResult = $questionAnswerLine->answer;

                                        $question->fetch($questionAnswerLine->fk_question);
                                        $answerList = $answer->fetchAll('ASC', 'position', '', '', ['fk_question' => $questionAnswerLine->fk_question]);

                                        $answersArray = [];
                                        if (is_array($answerList) && !empty($answerList)) {
                                            foreach ($answerList as $answerSingle) {
                                                $answersArray[$answerSingle->position] = $answerSingle->value;
                                            }
                                        }

                                        switch ($question->type) {
                                            case $langs->transnoentities('OkKo') :
                                            case $langs->transnoentities('OkKoToFixNonApplicable') :
                                            case $langs->transnoentities('UniqueChoice') :
                                                $tmparray['answer'] = $answersArray[$answerResult];
                                                break;
                                            case $langs->transnoentities('Text') :
                                            case $langs->transnoentities('Range') :
                                                $tmparray['answer'] = $answerResult;
                                                break;
                                            case $langs->transnoentities('Percentage') :
                                                $tmparray['answer'] = $answerResult . ' %';
                                                break;
                                            case $langs->transnoentities('MultipleChoices') :
                                                $answers = preg_split('/,/', $answerResult);
                                                $tmparray['answer'] = '';
                                                foreach ($answers as $answerId) {
                                                    $tmparray['answer'] .= $answersArray[$answerId] . ', ';
                                                }
                                                $tmparray['answer'] = rtrim($tmparray['answer'], ', ');
                                                break;
                                        }

                                        $path     = $conf->dolismq->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $question->ref;
                                        $fileList = dol_dir_list($path, 'files');
                                        // Fill an array with photo path and ref of the answer for next loop.
                                        if (is_array($fileList) && !empty($fileList)) {
                                            foreach ($fileList as $singleFile) {
                                                $fileSmall          = saturne_get_thumb_name($singleFile['name']);
                                                $image              = $path . '/thumbs/' . $fileSmall;
                                                $photoArray[$image] = $questionAnswerLine->ref;
                                            }
                                        }
                                    }

                                    unset($tmparray['object_fields']);
                                    unset($tmparray['object_array_options']);

                                    complete_substitutions_array($tmparray, $outputlangs, $object, $question, 'completesubstitutionarray_lines');
                                    // Call the ODTSubstitutionLine hook.
                                    $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $question];
                                    $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.
                                    foreach ($tmparray as $key => $val) {
                                        try {
                                            if (file_exists($val)) {
                                                $listlines->setImage($key, $val);
                                            } elseif (empty($val)) {
                                                $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                            } else {
                                                $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                            }
                                        } catch (OdfException|SegmentException $e) {
                                            dol_syslog($e->getMessage());
                                        }
                                    }
                                    $listlines->merge();
                                }
                                $odfHandler->mergeSegment($listlines);
                            }
                        }
                    }

                    // Get answer photos.
                    $foundtagforlines = 1;
                    try {
                        $listlines = $odfHandler->setSegment('photos');
                    } catch (OdfException $e) {
                        // We may arrive here if tags for lines not present into template.
                        $foundtagforlines = 0;
                        $listlines = '';
                        dol_syslog($e->getMessage());
                    }

                    // Loop on previous photos array
                    if ($foundtagforlines) {
                        if ($isPhotoDocument && is_array($photoArray) && !empty($photoArray)) {
                            foreach ($photoArray as $photoPath => $answerRef) {
                                $fileInfo = preg_split('/thumbs\//', $photoPath);
                                $name = end($fileInfo);

                                $tmparray['answer_ref'] = ($previousRef == $answerRef) ? '' : $langs->trans('Ref') . ' : ' . $answerRef;
                                $tmparray['photo_name'] = $name;
                                $tmparray['photo']      = $photoPath;

                                $previousRef = $answerRef;

                                foreach ($tmparray as $key => $val) {
                                    try {
                                        if (file_exists($val)) {
                                            $result = $listlines->setImage($key, $val);
                                        } elseif (empty($val)) {
                                            $listlines->setVars($key, '', true, 'UTF-8');
                                        } else {
                                            $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                        }
                                    } catch (OdfException|SegmentException $e) {
                                        dol_syslog($e->getMessage());
                                    }
                                }
                                $listlines->merge();
                            }
                            $odfHandler->mergeSegment($listlines);
                        } elseif ($isPhotoDocument) {
                            try {
                                $listlines->setVars('answer_ref', ' ', true, 'UTF-8');
                                $listlines->setVars('photo_name', ' ', true, 'UTF-8');
                                $listlines->setVars('photo', ' ', true, 'UTF-8');
                            } catch (SegmentException $e) {
                                dol_syslog($e->getMessage());
                            }
                            $listlines->merge();
                            $odfHandler->mergeSegment($listlines);
                        }
                    }
                } catch (OdfException $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($this->error, LOG_WARNING);
                    return -1;
                }

                // Replace labels translated
                $tmparray = $outputlangs->get_translations_for_substitutions();

                // Call the beforeODTSave hook.
                $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
                $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.

                $fileInfos = pathinfo($filename);
                $pdfName   = $fileInfos['filename'] . '.pdf';

                // Write new file.
                if (!empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->DOLISMQ_AUTOMATIC_PDF_GENERATION > 0) {
                    try {
                        $odfHandler->exportAsAttachedPDF($file);

                        global $moduleNameLowerCase;
                        $documentUrl = DOL_URL_ROOT . '/document.php';
                        setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode('controldocument/' . $object->ref . '/' . $pdfName) . '&entity=' . $conf->entity . '"' . '>' . $pdfName  . '</a>', []);
                    } catch (Exception $e) {
                        $this->error = $e->getMessage();
                        dol_syslog($e->getMessage());
                        setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), [], 'errors');
                    }
                }  else {
                    try {
                        $odfHandler->saveToDisk($file);
                    } catch (Exception $e) {
                        $this->error = $e->getMessage();
                        dol_syslog($e->getMessage());
                        return -1;
                    }
                }

                $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
                $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.

                if (!empty($conf->global->MAIN_UMASK)) {
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
                }

                $odfHandler = null; // Destroy object.

                $this->result = ['fullpath' => $file];

                return 1; // Success.
            } else {
                $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
                return -1;
            }
        }

        return -1;
    }
}
