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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/digiquali/digiqualidocuments/surveydocument/doc_surveydocument_odt.modules.php
 * \ingroup digiquali
 * \brief   File of class to build ODT survey document
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/mod_surveydocument_standard.php';
require_once __DIR__ . '/../../../../../lib/digiquali_sheet.lib.php';

require_once __DIR__ . '/../../../../../class/question.class.php';
require_once __DIR__ . '/../../../../../class/sheet.class.php';
require_once __DIR__ . '/../../../../../class/answer.class.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_surveydocument_odt extends SaturneDocumentModel
{
    /**
     * @var array Minimum version of PHP required by module
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public $phpmin = [7, 4];

    /**
     * @var string Dolibarr version of the loaded document
     */
    public string $version = 'dolibarr';

    /**
     * @var string Module
     */
    public string $module = 'digiquali';

    /**
     * @var string Document type
     */
    public string $document_type = 'surveydocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Return description of a module
     *
     * @param  Translate $langs Lang object to use for output
     * @return string           Description
     */
    public function info(Translate $langs): string
    {
        return parent::info($langs);
    }

    //@todo
    /**
     * Fill all odt tags for segments lines
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @return int                    1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        global $conf;

        $object = $moreParam['object'];

        // Replace tags of lines
        try {
            $this->setAttendantsSegment($odfHandler, $outputLangs, $moreParam);

            // Get questions
            $photoArray       = [];
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('questions');
            } catch (OdfException $e) {
                // We may arrive here if tags for lines not present into template
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {

                if (!empty($object)) {
                    $sheet = new Sheet($this->db);

                    $sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_sheet','', '', 'OR', 1, 'sourcetype', 0);
                    $questionIds = $sheet->linkedObjectsIds;

                    if (is_array($questionIds['digiquali_question']) && !empty($questionIds['digiquali_question'])) {
                        $controldet = new SurveyLine($this->db);
                        $question   = new Question($this->db);
                        $answer     = new Answer($this->db);
                        foreach ($questionIds['digiquali_question'] as $questionId) {
                            $question->fetch($questionId);

                            $controldets = $controldet->fetchFromParentWithQuestion($object->id, $questionId);

                            $tmpArray['ref']         = $question->ref;
                            $tmpArray['label']       = $question->label;
                            $tmpArray['description'] = strip_tags($question->description);

                            if (is_array($controldets) && !empty($controldets)) {
                                $questionAnswerLine     = array_shift($controldets);
                                $tmpArray['ref_answer'] = $questionAnswerLine->ref;
                                $tmpArray['comment']    = dol_htmlentitiesbr_decode(strip_tags($questionAnswerLine->comment, '<br>'));

                                $answerResult = $questionAnswerLine->answer;

                                $question->fetch($questionAnswerLine->fk_question);
                                $answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['fk_question' => $questionAnswerLine->fk_question]);

                                $answersArray = [];
                                if (is_array($answerList) && !empty($answerList)) {
                                    foreach ($answerList as $answerSingle) {
                                        $answersArray[$answerSingle->position] = $answerSingle->value;
                                    }
                                }

                                switch ($question->type) {
                                    case 'OkKo' :
                                    case 'OkKoToFixNonApplicable' :
                                    case 'UniqueChoice' :
                                        $tmpArray['answer'] = $answersArray[$answerResult];
                                        break;
                                    case 'Text' :
                                    case 'Range' :
                                        $tmpArray['answer'] = $answerResult;
                                        break;
                                    case 'Percentage' :
                                        $tmpArray['answer'] = $answerResult . ' %';
                                        break;
                                    case 'MultipleChoices' :
                                        $answers = explode(',', $answerResult);
                                        $tmpArray['answer'] = '';
                                        foreach ($answers as $answerId) {
                                            $tmpArray['answer'] .= $answersArray[$answerId] . ', ';
                                        }
                                        $tmpArray['answer'] = rtrim($tmpArray['answer'], ', ');
                                        break;
                                    default:
                                        $tmpArray['answer'] = '';
                                }

                                $path     = $conf->digiquali->multidir_output[$conf->entity] . '/survey/' . $object->ref . '/answer_photo/' . $question->ref;
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
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                        $odfHandler->mergeSegment($listLines);
                    }
                }
            }

            // Get answer photos
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('photos');
            } catch (OdfException $e) {
                // We may arrive here if tags for lines not present into template
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            // Loop on previous photos array
            if ($foundTagForLines) {
                if (is_array($photoArray) && !empty($photoArray)) {
                    foreach ($photoArray as $photoPath => $answerRef) {
                        $fileInfo = preg_split('/thumbs\//', $photoPath);
                        $name     = end($fileInfo);

                        $tmpArray['answer_ref'] = ($previousRef == $answerRef) ? '' : $outputLangs->trans('Ref') . ' : ' . $answerRef;
                        $tmpArray['media_name'] = $name;
                        $tmpArray['photo']      = $photoPath;

                        $previousRef = $answerRef;

                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                    }
                } else {
                    $tmpArray['answer_ref'] = ' ';
                    $tmpArray['media_name'] = ' ';
                    $tmpArray['photo']      = ' ';
                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
                $odfHandler->mergeSegment($listLines);
            }
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }
        return 0;
    }

    /**
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf;

        $object = $moreParam['object'];

        if (!empty($object->photo)) {
            $path      = $conf->digiquali->multidir_output[$conf->entity] . '/survey/' . $object->ref . '/photos';
            $fileSmall = saturne_get_thumb_name($object->photo);
            $image     = $path . '/thumbs/' . $fileSmall;
            $tmpArray['photoDefault'] = $image;
        } else {
            $noPhoto                  = '/public/theme/common/nophoto.png';
            $tmpArray['photoDefault'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $outputLangs->loadLangs(['products', 'bills', 'orders', 'contracts', 'projects', 'companies']);

        $sheet   = new Sheet($this->db);
        $project = new Project($this->db);

        $sheet->fetch($object->fk_sheet);
        $project->fetch($object->projectid);

        $object->fetchObjectLinked('', '', $object->id, 'digiquali_survey',  'OR', 1, 'sourcetype', 0);

        $linkableElements = get_sheet_linkable_objects();
        if (is_array($linkableElements) && !empty($linkableElements)) {
            foreach ($linkableElements as $linkableElement) {
                $nameField[$linkableElement['link_name']]  = $linkableElement['name_field'];
                $objectInfo[$linkableElement['link_name']] = ['title' => $linkableElement['langs'], 'className' => $linkableElement['className']];
            }
            foreach ($object->linkedObjectsIds as $linkedObjectType => $linkedObjectsIds) {
                $className    = $objectInfo[$linkedObjectType]['className'];
                $linkedObject = new $className($this->db);
                $result        = $linkedObject->fetch(array_shift($object->linkedObjectsIds[$linkedObjectType]));
                if ($result > 0) {
                    $objectName = '';
                    $objectNameField = $nameField[$linkedObjectType];
                    if (strstr($objectNameField, ',')) {
                        $nameFields = explode(', ', $objectNameField);
                        if (is_array($nameFields) && !empty($nameFields)) {
                            foreach ($nameFields as $subnameField) {
                                $objectName .= $linkedObject->$subnameField . ' ';
                            }
                        }
                    } else {
                        $objectName = $linkedObject->$objectNameField;
                    }
                    $tmpArray['object_label_ref'] .= $objectName . chr(0x0A);
                    $tmpArray['object_type'] = $outputLangs->transnoentities($objectInfo[$linkedObjectType]['title']) . ' : ';
                }
            }
        }

        $tmpArray['object_ref']              = $object->ref;
        $tmpArray['object_label_ref']        = rtrim($tmpArray['object_label_ref'], chr(0x0A));
        // @todo
        //$tmpArray['actioncom_creation_date'] = dol_print_date($object->date_creation, 'dayhour', 'tzuser');
        //$tmpArray['average'] = dol_print_date($object->date_creation, 'dayhour', 'tzuser');
        $tmpArray['project_label']           = $project->ref . ' - ' . $project->title;
        $tmpArray['sheet_ref']               = $sheet->ref;
        $tmpArray['sheet_label']             = $sheet->label;

        $tmpArray['public_note'] = $object->note_public;

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
