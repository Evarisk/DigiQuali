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
 *   	\file       view/control/control_card.php
 *		\ingroup    dolismq
 *		\brief      Page to create/edit/view control
 */

// Load DoliSMQ environment
if (file_exists('../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../dolismq.main.inc.php';
} elseif (file_exists('../../dolismq.main.inc.php')) {
	require_once __DIR__ . '/../../dolismq.main.inc.php';
} else {
	die('Include of dolismq main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/lib/object.lib.php';
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../class/dolismqdocuments/controldocument.class.php';
require_once __DIR__ . '/../../lib/dolismq_control.lib.php';
require_once __DIR__ . '/../../lib/dolismq_answer.lib.php';
require_once __DIR__ . '/../../core/modules/dolismq/control/mod_control_standard.php';
require_once __DIR__ . '/../../core/modules/dolismq/controldet/mod_controldet_standard.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other', 'bills', 'orders']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'controlcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize objects
// Technical objets
$object           = new Control($db);
$controldet       = new ControlLine($db);
$document         = new ControlDocument($db);
$signatory        = new SaturneSignature($db, 'dolismq');
$sheet            = new Sheet($db);
$question         = new Question($db);
$answer           = new Answer($db);
$usertmp          = new User($db);
$product          = new Product($db);
$project          = new Project($db);
$task             = new Task($db);
$thirdparty       = new Societe($db);
$contact          = new Contact($db);
$productlot       = new Productlot($db);
$invoice          = new Facture($db);
$order            = new Commande($db);
$contract         = new Contrat($db);
$ticket           = new Ticket($db);
$extrafields      = new ExtraFields($db);
$ecmfile 		  = new EcmFiles($db);
$ecmdir           = new EcmDirectory($db);
$category         = new Categorie($db);
$refControlMod    = new $conf->global->DOLISMQ_CONTROL_ADDON($db);
$refControlDetMod = new $conf->global->DOLISMQ_CONTROLDET_ADDON($db);

// View objects
$form        = new Form($db);
$formproject = new FormProjets($db);

$hookmanager->initHooks(array('controlcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST('search_all', 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread   = $user->rights->dolismq->control->read;
$permissiontoadd    = $user->rights->dolismq->control->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolismq->control->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->dolismq->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolismq/view/control/control_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/dolismq/view/control/control_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

	if ($action == 'confirm_delete' && $permissiontodelete) {
		$db->begin();

		$objecttmp = $object;
		$nbok = 0;
		$TMsg = array();
		$result = $objecttmp->fetch($id);

		if ($result > 0) {
			$result = $objecttmp->delete($user);

			if ($result > 0) {
				$db->commit();

				// Delete OK
				setEventMessages('RecordDeleted', null, 'mesgs');

				header('Location: ' .$backurlforlist);
				exit;
			} else {
				$error++;
				if (!empty($object->errors)) {
					setEventMessages(null, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, null, 'errors');
				}
			}
			$action = '';
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
        $options['attendants'] = GETPOST('clone_attendants');
        $options['photos']     = GETPOST('clone_photos');
		if ($object->id > 0) {
			$result = $object->createFromClone($user, $object->id, $options);
			if ($result > 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	include __DIR__ . '/../../../saturne/core/tpl/actions/edit_project_action.tpl.php';

	if ($action == 'set_categories' && $permissiontoadd) {
		if ($object->fetch($id) > 0) {
			$result = $object->setCategories(GETPOST('categories', 'array'));
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
			exit();
		}
	}

	if ($action == 'save') {
		$controldet = new ControlLine($db);
		$sheet->fetch($object->fk_sheet);
		$object->fetchObjectLinked($sheet->id, 'dolismq_sheet');
		$questionIds = $object->linkedObjectsIds['dolismq_question'];

		foreach ($questionIds as $questionId) {
			$controldettmp = $controldet;
			//fetch controldet avec le fk_question et fk_control, s'il existe on l'update sinon on le crée
			$result = $controldettmp->fetchFromParentWithQuestion($object->id, $questionId);

			if ($result > 0 && is_array($result)) {
				$controldettmp = array_shift($result);
				//sauvegarder réponse
				$questionAnswer = GETPOST('answer'.$questionId);

				if (!empty($questionAnswer)) {
					$controldettmp->answer = $questionAnswer;
				}

				//sauvegarder commentaire
				$comment = GETPOST('comment'.$questionId);

				if (dol_strlen($comment) > 0) {
					$controldettmp->comment = $comment;
				}

				$question->fetch($questionId);
				$controldettmp->update($user);
			} else {
				$controldettmp = $controldet;

				$controldettmp->ref = $refControlDetMod->getNextValue($controldettmp);

				$controldettmp->fk_control  = $object->id;
				$controldettmp->fk_question = $questionId;

				//sauvegarder réponse
				$questionAnswer = GETPOST('answer'.$questionId);

				if (!empty($questionAnswer)) {
					$controldettmp->answer = $questionAnswer;
				} else {
					$controldettmp->answer = '';
				}

				//sauvegarder commentaire
				$comment = GETPOST('comment'.$questionId);
				if (dol_strlen($comment) > 0) {
					$controldettmp->comment = $comment;
				} else {
					$controldettmp->comment = '';
				}

				$question->fetch($questionId);

				$controldettmp->entity = $conf->entity;
				$controldettmp->insert($user);
			}
		}

		setEventMessages($langs->trans('AnswerSaved'), array());
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
		exit;
	}

    // Action to build doc.
    if (($action == 'builddoc' || GETPOST('forcebuilddoc')) && $permissiontoadd) {
        $outputlangs = $langs;
        $newlang = '';

        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
            $newlang = GETPOST('lang_id', 'aZ09');
        }
        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        // To be sure vars is defined.
        if (empty($hidedetails)){
            $hidedetails = 0;
        }
        if (empty($hidedesc)) {
            $hidedesc = 0;
        }
        if (empty($hideref)) {
            $hideref = 0;
        }
        if (empty($moreparams)) {
            $moreparams = [];
        }

        if (GETPOST('forcebuilddoc')) {
            $model  = '';
            $modellist = saturne_get_list_of_models($db, $object->element . 'document');
            if (!empty($modellist)) {
                asort($modellist);
                $modellist = array_filter($modellist, 'saturne_remove_index');
                if (is_array($modellist)) {
                    $models = array_keys($modellist);
                }
            }
        } else {
            $model = GETPOST('model', 'alpha');
        }

        $moreparams['object'] = $object;
        $moreparams['user']   = $user;

        if ($object->status < $object::STATUS_LOCKED) {
            $moreparams['specimen'] = 1;
            $moreparams['zone']     = 'private';
        } else {
            $moreparams['specimen'] = 0;
        }

        $result = $document->generateDocument((!empty($models) ? $models[0] : $model), $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        if ($result <= 0) {
            setEventMessages($document->error, $document->errors, 'errors');
            $action = '';
        } elseif (empty($donotredirect)) {
            setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=dolismq&file=' . urlencode('controldocument/' . $object->ref . '/' . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc, []);
            $urltoredirect = $_SERVER['REQUEST_URI'];
            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
            $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
            $urltoredirect = preg_replace('/forcebuilddoc=1&?/', '', $urltoredirect); // To avoid infinite loop
            header('Location: ' . $urltoredirect . '#builddoc');
            exit;
        }
    }

	// Action to generate pdf from odt file
	include_once DOL_DOCUMENT_ROOT . '/custom/saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

	// Delete file in doc form
	if ($action == 'remove_file' && $permissiontodelete) {
		if ( ! empty($upload_dir)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

			$langs->load('other');
			$filetodelete = GETPOST('file', 'alpha');
			$file         = $upload_dir . '/' . $filetodelete;
			$ret          = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessages($langs->trans('FileWasRemoved', $filetodelete), null, 'mesgs');
			else setEventMessages($langs->trans('ErrorFailToDeleteFile', $filetodelete), null, 'errors');

			// Make a redirect to avoid to keep the remove_file into the url that create side effects
			$urltoredirect = $_SERVER['REQUEST_URI'];
			$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
			$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

			header('Location: ' . $urltoredirect);
			exit;
		} else {
			setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
		}
	}

	if ($action == 'confirm_setVerdict' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		$object->fetch($id);
		if ( ! $error) {
			$object->verdict = GETPOST('verdict', 'int');
			$object->note_public .= (!empty($object->note_public) ? chr(0x0A) : '') . GETPOST('noteControl');
			$result = $object->update($user);
			if ($result > 0) {
				// Set verdict Control
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set verdict Control error
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_VALIDATED
	if ($action == 'confirm_setValidated') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setValidated($user, false);
			if ($result > 0) {
				$controldet = new ControlLine($db);
				$sheet->fetch($object->fk_sheet);
				$object->fetchObjectLinked($sheet->id, 'dolismq_sheet');
				$questionIds = $object->linkedObjectsIds;
				foreach ($questionIds['dolismq_question'] as $questionId) {
					$controldettmp = $controldet;
					//fetch controldet avec le fk_question et fk_control, s'il existe on l'update sinon on le crée
					$result = $controldettmp->fetchFromParentWithQuestion($object->id, $questionId);

					//sauvegarder réponse
					$questionAnswer = GETPOST('answer'.$questionId);
					if (!empty($questionAnswer)) {
						$controldettmp->answer = $questionAnswer;
					}

					//sauvegarder commentaire
					$comment = GETPOST('comment'.$questionId);
					if (dol_strlen($comment) > 0) {
						$controldettmp->comment = $comment;
					}

					if ($result > 0 && is_array($result)) {
						$controldettmp = array_shift($result);

						$controldettmp->update($user);
					} else {
						if (empty($controldettmp->ref)) {
							$controldettmp->ref = $refControlDetMod->getNextValue($controldettmp);
						}

						$controldettmp->fk_control  = $object->id;
						$controldettmp->fk_question = $questionId;

						$controldettmp->entity = $conf->entity;

						$controldettmp->insert($user);
					}
				}
				// Set validated OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set validated KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_REOPENED
	if ($action == 'confirm_setReopened') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setDraft($user, false);
			if ($result > 0) {
				$object->verdict = null;
				$result = $object->update($user);
				// Set reopened OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set reopened KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_lock') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header('Location: ' . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_ARCHIVED.
	if ($action == 'confirm_archive' && $permissiontoadd) {
	$object->fetch($id);
	if (!$error) {
	$result = $object->setArchived($user);
	if ($result > 0) {
	// Set Archived OK.
	$urltogo = str_replace('__ID__', $result, $backtopage);
	$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
	header('Location: ' . $urltogo);
	exit;
	} elseif (!empty($object->errors)) { // Set Archived KO.
	setEventMessages('', $object->errors, 'errors');
	} else {
	setEventMessages($object->error, [], 'errors');
	}
	}
	}

  // Action to set status STATUS_ARCHIVED.
	if ($action == 'confirm_archive' && $permissiontoadd) {
		$object->fetch($id);
		if (!$error) {
			$result = $object->setArchived($user);
			if ($result > 0) {
				// Set Archived OK.
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
				header('Location: ' . $urltogo);
				exit;
			} elseif (!empty($object->errors)) { // Set Archived KO.
				setEventMessages('', $object->errors, 'errors');
			} else {
				setEventMessages($object->error, [], 'errors');
			}
		}
	}

	// Actions to send emails
	$triggersendname = 'CONTROL_SENTBYMAIL';
	$autocopy        = 'MAIN_MAIL_AUTOCOPY_AUDIT_TO';
	$trackid         = 'control' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$title    = $langs->trans('Control');
$help_url = 'FR:Module_DoliSMQ';

saturne_header(1,'', $title, $help_url);
$object->fetch(GETPOST('id'));

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewControl'), '', 'object_' . $object->picto);

	print '<form method="POST" id="createControlForm" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate control-table"><thead>'."\n";

	$object->fields['fk_user_controller']['default'] = $user->id;

	if (!empty(GETPOST('fk_sheet'))) {
		$sheet->fetch(GETPOST('fk_sheet'));
	}

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Categories
	if (!empty($conf->categorie->enabled)) {
		print '<tr><td>'.$langs->trans('Categories').'</td><td>';
		$categoryArborescence = $form->select_all_categories('control', '', 'parent', 64, 0, 1);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $categoryArborescence, GETPOST('categories', 'array'), '', 0, 'maxwidth500 widthcentpercentminusx');
		//print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=control&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
		print '</td></tr>';
	}

	//FK SHEET
	print '<tr><td class="fieldrequired">' . $langs->trans('SheetLinked') . '</td><td>';
	print img_picto('', 'list', 'class="pictofixedwidth"') . $sheet->selectSheetList(GETPOST('fk_sheet')?: $sheet->id);
	print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/custom/dolismq/view/sheet/sheet_card.php?action=create" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddSheet') . '"></span></a>';
	print '</td></tr></thead>';

	print '</table>';
	print '<hr>';

	print '<table class="border centpercent tableforfieldcreate control-table">'."\n";

	print '<tr><td>';
	print '<div class="fields-content">';

	//FK Product
	if ($conf->global->DOLISMQ_SHEET_LINK_PRODUCT && preg_match('/"product":1/',$sheet->element_linked)) {
		$productPost = GETPOST('fk_product') ?: (GETPOST('fromtype') == 'product' ? GETPOST('fromid') : 0);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('ProductOrServiceLinked') . '</td><td>';
		print img_picto('', 'product', 'class="pictofixedwidth"');
		$form->select_produits($productPost, 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProductsOrServices', 0, 'maxwidth500 widthcentpercentminusxx');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProduct') . '"></span></a>';
		print '</td></tr>';
	}

     // FK Productlot.
    if ($conf->global->DOLISMQ_SHEET_LINK_PRODUCTLOT && preg_match('/"productlot":1/', $sheet->element_linked)) {
        $productLotPost = GETPOST('fk_productlot') ?: (GETPOST('fromtype') == 'productbatch' ? GETPOST('fromid') : -1);
        print '<tr><td class="titlefieldcreate">' . $langs->trans('BatchLinked') . '</td><td class="lot-container">';
        print '<span class="lot-content">';
        print img_picto('', 'lot', 'class="pictofixedwidth"');
        if (preg_match('/"product":1/', $sheet->element_linked)) {
            $filter = ['customsql' => 'fk_product = ' . (dol_strlen(GETPOST('fk_product')) > 0 ? GETPOST('fk_product') : 0)];
        } else {
            $filter = [];
        }
        $productlots = saturne_fetch_all_object_type('Productlot', '', '', 0, 0, $filter);
        if (is_array($productlots) && !empty($productlots)) {
            $showEmpty = '1';
            foreach ($productlots as $productlot) {
                $arrayProductLots[$productlot->id] = $productlot->batch;
            }
        } else {
            $showEmpty = $langs->transnoentities('NoLotForThisProduct');
        }
        print Form::selectarray('fk_productlot', $arrayProductLots, $productLotPost, $showEmpty, 0, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusxx');
        print '</span>';
        print '</td></tr>';
    }
    print '</div>';

	//FK User
	if ($conf->global->DOLISMQ_SHEET_LINK_USER && preg_match('/"user":1/',$sheet->element_linked)) {
		$userPost = GETPOST('fk_user') ?: (GETPOST('fromtype') == 'user' ? GETPOST('fromid') : -1);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('UserLinked') . '</td><td>';
		print img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers($userPost, 'fk_user', $langs->trans('SelectUser'), null, 0, '', '', '0', 0, 0, '', 0, '', 'maxwidth500 widthcentpercentminusxx');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddUser') . '"></span></a>';
		print '</td></tr>';
	}

	//FK Soc
	if ($conf->global->DOLISMQ_SHEET_LINK_THIRDPARTY && preg_match('/"thirdparty":1/',$sheet->element_linked)) {
		$thirdpartyPost = GETPOST('fk_soc') ?: (GETPOST('fromtype') == 'societe' ? GETPOST('fromid') : 0);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('ThirdPartyLinked') . '</td><td>';
		print img_picto('', 'building', 'class="pictofixedwidth"') . $form->select_company($thirdpartyPost, 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), 0, 'maxwidth500 widthcentpercentminusxx');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddThirdParty') . '"></span></a>';
		print '</td></tr>';
	}

	// FK Contact
	if ($conf->global->DOLISMQ_SHEET_LINK_CONTACT && preg_match('/"contact":1/',$sheet->element_linked)) {
		$contactPost = GETPOST('fk_contact') ?: (GETPOST('fromtype') == 'contact' ? GETPOST('fromid') : 0);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('ContactLinked') . '</td><td>';
		// If no fk_soc, set to -1 to avoid full contacts list
		print img_picto('', 'address', 'class="pictofixedwidth"') . $form->selectcontacts(((GETPOST('fk_soc') > 0) ? GETPOST('fk_soc') : 0), $contactPost, 'fk_contact', 1, '', '', 0, 'maxwidth500 widthcentpercentminusxx');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/contact/card.php?action=create' . ((GETPOST('fk_soc') > 0) ? '&socid=' . GETPOST('fk_soc') : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddContact') . '"></span></a>';
		print '</td></tr>';
	}

	//FK Project
	if ($conf->global->DOLISMQ_SHEET_LINK_PROJECT && preg_match('/"project":1/',$sheet->element_linked)) {
		$projectPost = GETPOST('fk_project') ?: (GETPOST('fromtype') == 'project' ? GETPOST('fromid') : 0);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('ProjectLinked') . '</td><td>';
		print img_picto('', 'project', 'class="pictofixedwidth"') . $formproject->select_projects((!empty(GETPOST('fk_soc')) ? GETPOST('fk_soc') : -1), $projectPost, 'fk_project', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
		print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/projet/card.php?action=create' . ((GETPOST('fk_soc') > 0) ? '&socid=' . GETPOST('fk_soc') : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProject') . '"></span></a>';
		print '</td></tr>';
	}

	//FK Task
	if ($conf->global->DOLISMQ_SHEET_LINK_TASK && preg_match('/"task":1/',$sheet->element_linked)) {
		$taskPost = GETPOST('fk_task') ?: (GETPOST('fromtype') == 'project_task' ? GETPOST('fromid') : 0);
		print '<tr><td class="titlefieldcreate">' . $langs->trans('TaskLinked');
		print '</td><td class="task-container">';
		print '<span class="task-content">';
		dol_strlen(GETPOST('fk_project')) > 0 ? $project->fetch(GETPOST('fk_project')) : 0;
		print img_picto('', 'projecttask', 'class="pictofixedwidth"');
		$formproject->selectTasks((!empty(GETPOST('fk_soc')) ? GETPOST('fk_soc') : 0), $taskPost, 'fk_task', 24, 0, '1', 1, 0, 0, 'maxwidth500 widthcentpercentminusxx', GETPOST('fk_project') ?: 0, '');
		print '</span>';
		print '</td></tr>';
	}
	print '</div>';

    // FK Invoice.
    if ($conf->global->DOLISMQ_SHEET_LINK_INVOICE && preg_match('/"invoice":1/', $sheet->element_linked)) {
        $invoicePost = GETPOST('fk_invoice') ?: (GETPOST('fromtype') == 'facture' ? GETPOST('fromid') : -1);
        print '<tr><td class="titlefieldcreate">' . $langs->trans('InvoiceLinked') . '</td><td>';
        print img_picto('', 'bill', 'class="pictofixedwidth"');
        $invoices = saturne_fetch_all_object_type('Facture');
        if (is_array($invoices) && !empty($invoices)) {
            foreach ($invoices as $invoice) {
                $arrayInvoices[$invoice->id] = $invoice->ref;
            }
        }
        print Form::selectarray('fk_invoice', $arrayInvoices, $invoicePost, '1', 0, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusxx');
        print '</td></tr>';
    }
    print '</div>';

    // FK Order.
    if ($conf->global->DOLISMQ_SHEET_LINK_ORDER && preg_match('/"order":1/', $sheet->element_linked)) {
        $orderPost = GETPOST('fk_order') ?: (GETPOST('fromtype') == 'commande' ? GETPOST('fromid') : -1);
        print '<tr><td class="titlefieldcreate">' . $langs->trans('OrderLinked') . '</td><td>';
        print img_picto('', 'order', 'class="pictofixedwidth"');
        $orders = saturne_fetch_all_object_type('Commande');
        if (is_array($orders) && !empty($orders)) {
            foreach ($orders as $order) {
                $arrayOrders[$order->id] = $order->ref;
            }
        }
        print Form::selectarray('fk_order', $arrayOrders, $orderPost, '1', 0, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusxx');
        print '</td></tr>';
    }
    print '</div>';

    // FK Contract.
    if ($conf->global->DOLISMQ_SHEET_LINK_CONTRACT && preg_match('/"contract":1/', $sheet->element_linked)) {
        $contractPost = GETPOST('fk_contract') ?: (GETPOST('fromtype') == 'contrat' ? GETPOST('fromid') : -1);
        print '<tr><td class="titlefieldcreate">' . $langs->trans('ContractLinked') . '</td><td>';
        print img_picto('', 'contract', 'class="pictofixedwidth"');
        $contracts = saturne_fetch_all_object_type('Contrat');
        if (is_array($contracts) && !empty($contracts)) {
            foreach ($contracts as $contract) {
                $arrayContracts[$contract->id] = $contract->ref;
            }
        }
        print Form::selectarray('fk_contract', $arrayContracts, $contractPost, '1', 0, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusxx');
        print '</td></tr>';
    }
    print '</div>';

    // FK Ticket.
    if ($conf->global->DOLISMQ_SHEET_LINK_TICKET && preg_match('/"ticket":1/', $sheet->element_linked)) {
        $ticketPost = GETPOST('fk_ticket') ?: (GETPOST('fromtype') == 'ticket' ? GETPOST('fromid') : -1);
        print '<tr><td class="titlefieldcreate">' . $langs->trans('TicketLinked') . '</td><td>';
        print img_picto('', 'ticket', 'class="pictofixedwidth"');
        $tickets = saturne_fetch_all_object_type('Ticket');
        if (is_array($tickets) && !empty($tickets)) {
            foreach ($tickets as $ticket) {
                $arrayTickets[$ticket->id] = $ticket->ref;
            }
        }
        print Form::selectarray('fk_ticket', $arrayTickets, $ticketPost, '1', 0, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusxx');
        print '</td></tr>';
    }
    print '</div>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Create', 'Cancel', [], 0, 'wpeo-button');

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);

	$formconfirm = '';

	if ($action == 'setVerdict') {
//		//Form to close proposal (signed or not)
//		$answersArray = $controldet->fetchFromParent($object->id);
//		$answerOK = 0;
//		$answerKO = 0;
//		$answerRepair = 0;
//		$answerNotApplicable = 0;
//		if (is_array($answersArray) && !empty($answersArray)) {
//			foreach ($answersArray as $questionAnswer){
//				switch ($questionAnswer->answer){
//					case 1:
//						$answerOK++;
//						break;
//					case 2:
//						$answerKO++;
//						break;
//					case 3:
//						$answerRepair++;
//						break;
//					case 4:
//						$answerNotApplicable++;
//						break;
//				}
//			}
//		}

		$formquestion = array(
			array('type' => 'select', 'name' => 'verdict', 'label' => '<span class="fieldrequired">' . $langs->trans('VerdictControl') . '</span>', 'values' => array('1' => 'OK', '2' => 'KO'), 'select_show_empty' => 0),
//			array('type' => 'text', 'name' => 'OK', 'label' => '<span class="answer" value="1" style="pointer-events: none"><i class="fas fa-check"></i></span>', 'value' => $answerOK, 'moreattr' => 'readonly'),
//			array('type' => 'text', 'name' => 'KO', 'label' => '<span class="answer" value="2" style="pointer-events: none"><i class="fas fa-times"></i></span>', 'value' => $answerKO, 'moreattr' => 'readonly'),
//			array('type' => 'text', 'name' => 'Repair', 'label' => '<span class="answer" value="3" style="pointer-events: none"><i class="fas fa-tools"></i></span>', 'value' => $answerRepair, 'moreattr' => 'readonly'),
//			array('type' => 'text', 'name' => 'NotApplicable', 'label' => '<span class="answer" value="4" style="pointer-events: none">N/A</span>', 'value' => $answerNotApplicable, 'moreattr' => 'readonly'),
			array('type' => 'text', 'name' => 'noteControl', 'label' => '<div class="note-control" style="margin-top: 20px;">' . $langs->trans('NoteControl') . '</div>'),
		);

		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('SetOK/KO'), $text, 'confirm_setVerdict', $formquestion, '', 1, 300);
	}

	// SetValidated confirmation
	if ($action == 'setValidated') {
		$sheet->fetch($object->fk_sheet);
		$sheet->fetchQuestionsLinked($object->fk_sheet, 'dolismq_' . $sheet->element);
		$questionIds = $sheet->linkedObjectsIds['dolismq_question'];

		if (!empty($questionIds)) {
			$questionCounter = count($questionIds);
		} else {
			$questionCounter = 0;
		}

		$object->fetchLines();
		$answerCounter = 0;
		if (is_array($object->lines) && !empty($object->lines)) {
			foreach($object->lines as $objectLine) {
				if (dol_strlen($objectLine->answer) > 0) {
					$answerCounter++;
				}
			}
		}

		$questionConfirmInfo = $langs->trans('YouAnswered') . ' ' . $answerCounter . ' ' . $langs->trans('question(s)')  . ' ' . $langs->trans('On') . ' ' . $questionCounter . '.';
		if ($questionCounter - $answerCounter != 0) {
			$questionConfirmInfo .= '<br><b>' . $langs->trans('BewareQuestionsAnswered', $questionCounter - $answerCounter) . '</b>';
		}

		$questionConfirmInfo .= '<br><br><b>' . $langs->trans('ConfirmValidateControl') . '</b>';
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ValidateControl'), $questionConfirmInfo, 'confirm_setValidated', '', '', 1, 250);
	}

	// SetReopened confirmation
	if (($action == 'setReopened' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile))) || ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))) . '<br>' . $langs->trans('ConfirmReOpenControl', $object->ref), 'confirm_setReopened', '', 'yes', 'actionButtonReOpen', 350, 600);
	}

	// SetLocked confirmation
	if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        // Define confirmation messages.
        $formquestionclone = [
            ['type' => 'checkbox', 'name' => 'clone_attendants', 'label' => $langs->trans('CloneAttendants'), 'value' => 1],
            ['type' => 'checkbox', 'name' => 'clone_photos', 'label' => $langs->trans('ClonePhotos'), 'value' => 1]
        ];

		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'actionButtonClone', 350, 600);
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Delete') . ' ' . $langs->transnoentities('The' . ucfirst($object->element)), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = ['formConfirm' => $formconfirm];
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

    if ($conf->browser->layout == 'phone') {
        $onPhone = 1;
    } else {
        $onPhone = 0;
    }

	saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

	print '<div class="fichecenter controlInfo' . ($onPhone ? ' hidden' : '') . '">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	unset($object->fields['projectid']); // Hide field already shown in banner

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td>';
		if ($action != 'categories') {
            print '<td style="display: flex">' . ($object->status < Control::STATUS_LOCKED ? '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=categories&id=' . $object->id . '">' . img_edit($langs->trans('Modify')) . '</a>' : '<img src="" alt="">');
			print $form->showCategories($object->id, 'control', 1) . '</td>';
		}
		if ($permissiontoadd && $action == 'categories') {
			$categoryArborescence = $form->select_all_categories('control', '', 'parent', 64, 0, 1);
            $categoryArborescence = empty($categoryArborescence) ? [] : $categoryArborescence;
			if (is_array($categoryArborescence)) {
				// Categories
				print '<td>';
				print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="set_categories">';

				$cats = $category->containing($object->id, 'control');
				$arrayselected = array();
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}

				print img_picto('', 'category') . $form->multiselectarray('categories', $categoryArborescence, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print '<input type="submit" class="button button-edit small" value="'.$langs->trans('Save').'">';
				print '</form>';
				print '</td>';
			}
		}
		print '</tr>';
	}

	$object->fetchObjectLinked('', '', '', 'dolismq_control');

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_PRODUCT) && (!empty($object->linkedObjectsIds['product']))) {
		//FKProduct -- Produit
		print '<tr><td class="titlefield">';
		print $langs->trans('ProductOrService');
		print '</td>';
		print '<td>';
		$product->fetch(array_shift($object->linkedObjectsIds['product']));
		if ($product > 0) {
			print $product->getNomUrl(1);
		}
		print '<td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_PRODUCTLOT) && (!empty($object->linkedObjectsIds['productbatch']))) {
		//FKLot -- Numéro de série
		print '<tr><td class="titlefield">';
		print $langs->trans('Batch');
		print '</td>';
		print '<td>';
		$productlot->fetch(array_shift($object->linkedObjectsIds['productbatch']));
		if ($productlot > 0) {
			print $productlot->getNomUrl(1);
		}
		print '</td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_USER) && (!empty($object->linkedObjectsIds['user']))) {
		//Fk_soc - Tiers lié
		print '<tr><td class="titlefield">';
		print $langs->trans('User');
		print '</td>';
		print '<td>';
		$usertmp->fetch(array_shift($object->linkedObjectsIds['user']));
		if ($usertmp > 0) {
			print $usertmp->getNomUrl(1);
		}
		print '</td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_THIRDPARTY) && (!empty($object->linkedObjectsIds['societe']))) {
		//Fk_soc - Tiers lié
		print '<tr><td class="titlefield">';
		print $langs->trans('ThirdParty');
		print '</td>';
		print '<td>';
		$thirdparty->fetch(array_shift($object->linkedObjectsIds['societe']));
		if ($thirdparty > 0) {
			print $thirdparty->getNomUrl(1);
		}
		print '</td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_CONTACT) && (!empty($object->linkedObjectsIds['contact']))) {
		//Fk_contact - Contact/adresse
		print '<tr><td class="titlefield">';
		print $langs->trans('Contact');
		print '</td>';
		print '<td>';
		$contact->fetch(array_shift($object->linkedObjectsIds['contact']));
		if ($contact > 0) {
			print $contact->getNomUrl(1);
		}
		print '</td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_PROJECT) && (!empty($object->linkedObjectsIds['project']))) {
		//Fk_project - Projet lié
		print '<tr><td class="titlefield">';
		print $langs->trans('Project');
		print '</td>';
		print '<td>';
		$project->fetch(array_shift($object->linkedObjectsIds['project']));
		if ($project > 0) {
			print $project->getNomUrl(1, '', 1);
		}
		print '</td></tr>';
	}

	if (!empty($conf->global->DOLISMQ_SHEET_LINK_TASK) && (!empty($object->linkedObjectsIds['project_task']))) {
		//Fk_task - Tâche liée
		print '<tr><td class="titlefield">';
		print $langs->trans('Task');
		print '</td>';
		print '<td>';
		$task->fetch(array_shift($object->linkedObjectsIds['project_task']));
		if ($task > 0) {
			print $task->getNomUrl(1);
		}
		print '</td></tr>';
	}

    if (!empty($conf->global->DOLISMQ_SHEET_LINK_INVOICE) && (!empty($object->linkedObjectsIds['facture']))) {
        //Fk_invoice - Facture liée
        print '<tr><td class="titlefield">';
        print $langs->trans('Invoice');
        print '</td>';
        print '<td>';
        $invoice->fetch(array_shift($object->linkedObjectsIds['facture']));
        if ($invoice > 0) {
            print $invoice->getNomUrl(1);
        }
        print '</td></tr>';
    }

    if (!empty($conf->global->DOLISMQ_SHEET_LINK_ORDER) && (!empty($object->linkedObjectsIds['commande']))) {
        //Fk_order - Commande liée
        print '<tr><td class="titlefield">';
        print $langs->trans('Order');
        print '</td>';
        print '<td>';
        $order->fetch(array_shift($object->linkedObjectsIds['commande']));
        if ($order > 0) {
            print $order->getNomUrl(1);
        }
        print '</td></tr>';
    }

    if (!empty($conf->global->DOLISMQ_SHEET_LINK_CONTRACT) && (!empty($object->linkedObjectsIds['contrat']))) {
        //Fk_contract - Contrat lié
        print '<tr><td class="titlefield">';
        print $langs->trans('Contract');
        print '</td>';
        print '<td>';
        $contract->fetch(array_shift($object->linkedObjectsIds['contrat']));
        if ($contract > 0) {
            print $contract->getNomUrl(1);
        }
        print '</td></tr>';
    }

    if (!empty($conf->global->DOLISMQ_SHEET_LINK_TICKET) && (!empty($object->linkedObjectsIds['ticket']))) {
        //Fk_ticket - Ticket lié
        print '<tr><td class="titlefield">';
        print $langs->trans('Ticket');
        print '</td>';
        print '<td>';
        $ticket->fetch(array_shift($object->linkedObjectsIds['ticket']));
        if ($ticket > 0) {
            print $ticket->getNomUrl(1);
        }
        print '</td></tr>';
    }

	print '<tr class="linked-medias photo question-table"><td class=""><label for="photos">' . $langs->trans("Photo") . '</label></td><td class="linked-medias-list">';
    $pathPhotos = $conf->dolismq->multidir_output[$conf->entity] . '/control/'. $object->ref . '/photos/';
    $fileArray  = dol_dir_list($pathPhotos, 'files');
	?>
	<span class="add-medias" <?php echo ($object->status != Control::STATUS_LOCKED) ? '' : 'style="display:none"' ?>>
		<input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
		<label for="fast-upload-photo-default">
			<div class="wpeo-button button-square-50">
				<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
			</div>
		</label>
		<input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>"/>
		<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
			<input type="hidden" class="modal-to-open" value="media_gallery"/>
			<input type="hidden" class="from-type" value="control"/>
			<input type="hidden" class="from-subtype" value="photo"/>
			<input type="hidden" class="from-subdir" value="photos"/>
			<input type="hidden" class="from-id" value="<?php echo $object->id?>"/>
			<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</span>
	<?php
	$relativepath = 'dolismq/medias/thumbs';
	print saturne_show_medias_linked('dolismq', $pathPhotos, 'small', 0, 0, 0, 0, 50, 50, 0, 0, 0, 'control/'. $object->ref . '/photos/', $object, 'photo', $object->status != Control::STATUS_LOCKED, $permissiontodelete && $object->status != Control::STATUS_LOCKED);
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	if ($permissiontoadd > 0 && $object->status < 1) {
		$user->rights->control = new stdClass();
		$user->rights->control->write = 1;
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php'; ?>
	<?php

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?action=save&id='.$object->id.'" id="saveControl" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="save">';

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		$displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
		print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';

		if (empty($reshook)) {
			// Save question answer
			$displayButton = $onPhone ? '<i class="fas fa-save fa-2x"></i>' : '<i class="fas fa-save"></i>' . ' ' . $langs->trans('Save');
			if ($object->status == $object::STATUS_DRAFT) {
				print '<span class="butActionRefused" id="saveButton" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=save' . '">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeDraft')) . '">' . $displayButton . '</span>';
			}

			// Validate
			$displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
			if ($object->status == $object::STATUS_DRAFT) {
				print '<a class="validateButton butAction" id="validateButton" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setValidated&token=' . newToken() . '">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeDraft')) . '">' . $displayButton . '</span>';
			}

			// ReOpen
			$displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('ReOpenDoli');
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonReOpen" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setDraft' . '">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidated')) . '">' . $displayButton . '</span>';
			}

			// Set verdict control
			$displayButton = $onPhone ? '<i class="far fa-check-circle fa-2x"></i>' : '<i class="far fa-check-circle"></i>' . ' ' . $langs->trans('SetOK/KO');
			if ($object->status == $object::STATUS_VALIDATED && $object->verdict == null) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setVerdict&token=' . newToken() . '">' . $displayButton . '</a>';
				}
			} elseif ($object->status == $object::STATUS_DRAFT) {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidatedToSetVerdict')) . '">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlVerdictSelected'))  . '">' . $displayButton . '</span>';
			}

            // Sign
            $displayButton = $onPhone ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->trans('Sign');
            if ($object->status == $object::STATUS_VALIDATED && !empty($object->verdict) && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name=DoliSMQ&object_type=' . $object->element . '&document_type=ControlDocument&attendant_table_mode=simple', 3) . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

			// Lock
			$displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
			if ($object->status == $object::STATUS_VALIDATED && $object->verdict != null && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
				print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ControlMustBeValidatedToLock')) . '">' . $displayButton . '</span>';
			}

			// Send email
			$displayButton = $onPhone ? '<i class="fas fa-paper-plane fa-2x"></i>' : '<i class="fas fa-paper-plane"></i>' . ' ' . $langs->trans('SendMail') . ' ';
			if ($object->status == $object::STATUS_LOCKED) {
                $fileparams = dol_most_recent_file($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref);
                $file       = $fileparams['fullname'];
                if (file_exists($file) && !strstr($fileparams['name'], 'specimen')) {
                    $forcebuilddoc = 0;
                } else {
                    $forcebuilddoc = 1;
                }
				print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=' . $forcebuilddoc . '&mode=init#formmailbeforetitle', '', $object->status == $object::STATUS_LOCKED);
			} else {
				print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans('ControlMustBeLockedToSendEmail')) . '">' . $displayButton . '</span>';
			}

            // Archive
			$displayButton = $onPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
            if ($object->status == $object::STATUS_LOCKED) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_archive&token=' . newToken() . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

			// Delete (need delete permission, or if draft, just need create/modify permission)
			$displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
			print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>';
	}

	// QUESTION LINES
	print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';

	$sheet->fetch($object->fk_sheet);
	$sheet->fetchQuestionsLinked($object->fk_sheet, 'dolismq_' . $sheet->element);
	$questionIds = $sheet->linkedObjectsIds['dolismq_question'];

	if (is_array($questionIds) && !empty($questionIds)) {
		ksort($questionIds);
	}
	if (!empty($questionIds)) {
		$questionCounter = count($questionIds);
	} else {
		$questionCounter = 0;
	}

	$object->fetchLines();
	$answerCounter = 0;
	if (is_array($object->lines) && !empty($object->lines)) {
		foreach($object->lines as $objectLine) {
			if (dol_strlen($objectLine->answer) > 0) {
				$answerCounter++;
			}
		}
	}

	print $langs->trans('YouAnswered') . ' ' . '<span class="answerCounter">'. $answerCounter .'</span>' . ' ' . $langs->trans('question(s)') . ' ' . $langs->trans('On') . ' ' . $questionCounter;

	print load_fiche_titre($langs->trans('LinkedQuestionsList'), '', ''); ?>

	<?php print '<div id="tablelines" class="control-audit noborder noshadow" width="100%">';

	global $forceall, $forcetoshowtitlelines;

	if (empty($forceall)) $forceall = 0;

	// Define colspan for the button 'Add'
	$colspan = 3;

	// Lines
	if (is_array($questionIds) && !empty($questionIds)) {
		foreach ($questionIds as $questionId) {
			$result = $controldet->fetchFromParentWithQuestion($object->id, $questionId);
			$questionAnswer = 0;
			$comment = '';
			if ($result > 0 && is_array($result)) {
				$itemControlDet = array_shift($result);
				$questionAnswer = $itemControlDet->answer;
				$comment = $itemControlDet->comment;
			}
			$item = $question;
			$item->fetch($questionId);
			?>
			<div class="wpeo-table table-flex table-3 table-id-<?php echo $item->id ?>">
				<div class="table-row">
					<!-- Contenu et commentaire -->
					<div class="table-cell table-full">
						<div class="label"><strong><?php print $item->ref . ' - ' . $item->label; ?></strong></div>
						<div class="description"><?php print $item->description; ?></div>
						<div class="question-comment-container">
							<div class="question-ref">
								<?php
								if ( ! empty( $itemControlDet->ref ) ) {
									print '<span class="question-ref-title">' . $itemControlDet->ref . '</span> :';
								} ?>
                            </div>
                            <?php if ($item->type == 'Text') : ?>
                            <div class="<?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
                                <?php
                                print '<span>' . $langs->trans('Answer') . ' : </span>';
                                $object->status > $object::STATUS_DRAFT ? print $questionAnswer :
                                print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'"class="question-textarea input-answer ' . ($object->status > 0 ? 'disable' : '') . '" value="'. $questionAnswer .'">';
                                ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($item->enter_comment > 0) : ?>
                                <?php print $langs->trans('Comment') . ' : '; ?>
                            <?php endif; ?>
							<?php if ($item->enter_comment > 0) : ?>
								<?php if ($object->status > 0 ) : ?>
									<?php print $comment; ?>
								<?php else : ?>
									<?php print '<input class="question-textarea question-comment" name="comment'. $item->id .'" id="comment'. $item->id .'" value="'. $comment .'" '. ($object->status == 2 ? 'disabled' : '').'>'; ?>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
					<!-- Photo OK KO -->
					<?php if ($item->show_photo > 0) : ?>
						<div class="table-cell table-450 cell-photo-check wpeo-table">
						<?php
						if (!empty($conf->global->DOLISMQ_CONTROL_DISPLAY_MEDIAS)) :
							print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ok', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, 'question/'. $item->ref . '/photo_ok', $item, 'photo_ok', 0, 0, 0,1, 'photo-ok', 0);
							print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/question/'. $item->ref . '/photo_ko', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, 'question/'. $item->ref . '/photo_ko', $item, 'photo_ko', 0, 0, 0,1, 'photo-ko', 0);
						endif;
						?>
					</div>
					<?php endif; ?>
				</div>
				<div class="table-row <?php echo ($onPhone ? 'center' : ''); ?>">
					<!-- Galerie -->
					<?php if ($item->authorize_answer_photo > 0) : ?>
						<div class="table-cell table-full linked-medias answer_photo_<?php echo $item->id ?>">
						<?php if ($object->status == 0 ) : ?>
							<input hidden multiple class="fast-upload" id="fast-upload-answer-photo<?php echo $item->id ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
							<input type="hidden" class="question-answer-photo" id="answer_photo_<?php echo $item->id ?>" name="answer_photo_<?php echo $item->id ?>" value=""/>
							<label for="fast-upload-answer-photo<?php echo $item->id ?>">
								<div class="wpeo-button button-square-50">
									<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
								</div>
							</label>
							<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="<?php echo $item->id ?>">
								<input type="hidden" class="modal-to-open" value="media_gallery"/>
								<input type="hidden" class="from-id" value="<?php echo $object->id ?>"/>
								<input type="hidden" class="from-type" value="<?php echo $object->element ?>"/>
								<input type="hidden" class="from-subtype" value="answer_photo_<?php echo $item->id ?>"/>
								<input type="hidden" class="from-subdir" value="answer_photo/<?php echo $item->ref ?>"/>
								<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
							</div>
						<?php endif; ?>
						<?php $relativepath = 'dolismq/medias/thumbs';
						print saturne_show_medias_linked('dolismq', $conf->dolismq->multidir_output[$conf->entity] . '/control/'. $object->ref . '/answer_photo/' . $item->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'control/'. $object->ref . '/answer_photo/' . $item->ref, $item, '', 0, $object->status == 0, 1);
						?>
					</div>
					<?php endif; ?>
					<?php
					$pictosArray = get_answer_pictos_array();
					?>
					<?php if ($item->type == 'MultipleChoices') :
						$answerList = $answer->fetchAll('ASC','position','','', ['fk_question' => $item->id]);
						?>
						<div class="table-cell table-end select-answer answer-cell <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
							<?php
							if (preg_match('/,/', $questionAnswer)) {
								$questionAnswers = preg_split('/,/', $questionAnswer);
							} else {
								$questionAnswers = [$questionAnswer];
							}

							print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';
							if (is_array($answerList) && !empty($answerList)) {
								foreach($answerList as $answerLinked) {
									print '<input type="hidden" class="answer-color answer-color-'. $answerLinked->position .'" value="'. $answerLinked->color .'">';
									print '<span style="'. (in_array($answerLinked->position, $questionAnswers) ? 'background:'. $answerLinked->color .'; ' : '') .'color:'. $answerLinked->color .';" class="answer multiple-answers square ' . ($object->status > 0 ? 'disable' : '') . ' ' . (in_array($answerLinked->position, $questionAnswers) ? 'active' : '') . '" value="'. $answerLinked->position .'">';
									if (!empty($answerLinked->pictogram)) {
										print $pictosArray[$answerLinked->pictogram]['picto_source'];
									} else {
										print $answerLinked->value;
									}
									print '</span>';
								}
							}
							?>
						</div>
					<?php elseif ($item->type == 'UniqueChoice' || $item->type == 'OkKo' || $item->type == 'OkKoToFixNonApplicable') :
						$answerList = $answer->fetchAll('ASC','position','','', ['fk_question' => $item->id]);
						?>
						<div class="table-cell table-end select-answer answer-cell <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
							<?php
							print '<input type="hidden" class="question-answer" name="answer'. $item->id .'" id="answer'. $item->id .'" value="0">';
							if (is_array($answerList) && !empty($answerList)) {
								foreach($answerList as $answerLinked) {
									print '<input type="hidden" class="answer-color answer-color-'. $answerLinked->position .'" value="'. $answerLinked->color .'">';
									print '<span style="'. ($questionAnswer == $answerLinked->position ? 'background:'. $answerLinked->color .'; ' : '') .'color:'. $answerLinked->color .';" class="answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $answerLinked->position .'">';
									if (!empty($answerLinked->pictogram)) {
										print $pictosArray[$answerLinked->pictogram]['picto_source'];
									} else {
										print $answerLinked->value;
									}
									print '</span>';
								}
							}
							?>
						</div>
                    <?php elseif ($item->type == 'Percentage') : ?>
                        <div class="table-cell table-end answer-cell table-flex <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
                            <?php
                            print '<span class="table-cell" value="">';
                            print $langs->transnoentities('Answer') . ' : ';
                            print '</span>';
                            print '<span class="table-cell" value="">';
                            print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'" type="number" min="0" max="100" class="input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $questionAnswer .'"> %';
                            print '</span>';
                            ?>
                        </div>
                    <?php elseif ($item->type == 'Range') : ?>
                        <div class="table-cell table-end answer-cell table-flex <?php echo ($object->status > 0) ? 'style="pointer-events: none"' : '' ?>">
                            <?php
                            print '<span class="table-cell" value="">';
                            print $langs->transnoentities('Answer') . ' : ';
                            print '</span>';
                            print '<span class="table-cell" value="">';
                            print '<input '. ($object->status > $object::STATUS_DRAFT ? 'disabled' : '') .' name="answer'. $item->id .'" id="answer'. $item->id .'" type="number" class="input-answer ' . ($object->status > 0 ? 'disable' : '') . ' ' . ($questionAnswer == $answerLinked->position ? 'active' : '') . '" value="'. $questionAnswer .'">';
                            print '</span>';
                            ?>
                        </div>
					<?php endif; ?>
					</div>
			    </div>
			<?php
		}
	}

	print '</div>';
	print '</div>';
	print '</form>';
	print dol_get_fiche_end();

	$includedocgeneration = 1;
	if ($includedocgeneration) {
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

		$objref = dol_sanitizeFileName($object->ref);
		$dirFiles = $object->element . 'document/' . $objref;
		$filedir = $upload_dir . '/' . $dirFiles;
		$urlsource = $_SERVER['PHP_SELF'] . '?id=' . $id;

		$defaultmodel = 'controldocument_odt';
		$title = $langs->trans('WorkUnitDocument');

		print saturne_show_documents('dolismq:ControlDocument', $dirFiles, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object, 0, 'remove_file', (($object->status > $object::STATUS_DRAFT) ? 1 : 0), $langs->trans('ControlMustBeValidatedToGenerated'));
		print '</div>';

		print '</div><div class="fichehalfright">';

		$maxEvent = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $maxEvent, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action == 'presend') {
		$langs->load('mails');

        $ref = dol_sanitizeFileName($object->ref);
        $filelist = dol_dir_list($upload_dir . '/' . $object->element . 'document' . '/' . $ref, 'files', 0, '', '', 'date', SORT_DESC);
        if (!empty($filelist) && is_array($filelist)) {
            $filetype = ['controldocument' => 0];
            foreach ($filelist as $file) {
                if (!strstr($file['name'], 'specimen')) {
                    if (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('controldocument'))) && $filetype['controldocument'] == 0) {
                        $files[] = $file['fullname'];
                        $filetype['controldocument'] = 1;
                    }
                }
            }
        }

        // Define output language
        $outputlangs = $langs;
        $newlang     = '';
        if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
            $newlang = $object->thirdparty->default_lang;
            if (GETPOST('lang_id', 'aZ09')) {
                $newlang = GETPOST('lang_id', 'aZ09');
            }
        }

        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
        print '<div class="clearboth"></div>';
        print '<br>';
        print load_fiche_titre($langs->trans('SendMail'), '', $object->picto);

        print dol_get_fiche_head();

        // Create form for email.
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
        $formmail = new FormMail($db);

        $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
        $formmail->fromtype = (GETPOST('fromtype') ?GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

        if ($formmail->fromtype === 'user') {
            $formmail->fromid = $user->id;
        }

		$formmail->withfrom = 1;

        // Define $liste, a list of recipients with email inside <>.
        $liste = [];
        if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
            $object->fetch_thirdparty();
        }
        if (is_object($object->thirdparty)) {
            foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
                $liste[$key] = $value;
            }
        }

        if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
            $listeuser = [];
            $fuserdest = new User($db);

            $result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, ['customsql' => "t.statut = 1 AND t.employee = 1 AND t.email IS NOT NULL AND t.email <> ''"], 'AND', true);
            if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
                foreach ($fuserdest->users as $uuserdest) {
                    $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
                }
            } elseif ($result < 0) {
                setEventMessages(null, $fuserdest->errors, 'errors');
            }
            if (count($listeuser) > 0) {
                $formmail->withtouser = $listeuser;
                $formmail->withtoccuser = $listeuser;
            }
        }

        //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
        if (!isset($arrayoffamiliestoexclude)) {
            $arrayoffamiliestoexclude = null;
        }

        // Make substitution in email content.
        if ($object) {
            // First we set ->substit (useless, it will be erased later) and ->substit_lines.
            $formmail->setSubstitFromObject($object, $langs);
        }
        $substitutionarray                = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
        $substitutionarray['__TYPE__']    = $langs->trans(ucfirst($object->element));
        $substitutionarray['__THETYPE__'] = $langs->trans('The' . ucfirst($object->element));

        $parameters = ['mode' => 'formemail'];
        complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

        // Find all external contact addresses
        $tmpobject  = $object;
        $contactarr = [];
        $contactarr = $tmpobject->liste_contact(-1);

        if (is_array($contactarr) && count($contactarr) > 0) {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $contactstatic = new Contact($db);
            $tmpcompany = new Societe($db);

            foreach ($contactarr as $contact) {
                $contactstatic->fetch($contact['id']);
                // Complete substitution array
                $substitutionarray['__CONTACT_NAME_' . $contact['code'] . '__']      = $contactstatic->getFullName($outputlangs, 1);
                $substitutionarray['__CONTACT_LASTNAME_' . $contact['code'] . '__']  = $contactstatic->lastname;
                $substitutionarray['__CONTACT_FIRSTNAME_' . $contact['code'] . '__'] = $contactstatic->firstname;
                $substitutionarray['__CONTACT_TITLE_' . $contact['code'] . '__']     = $contactstatic->getCivilityLabel();

                // Complete $liste with the $contact
                if (empty($liste[$contact['id']])) {    // If this contact id not already into the $liste.
                    $contacttoshow = '';
                    if (isset($object->thirdparty) && is_object($object->thirdparty)) {
                        if ($contactstatic->fk_soc != $object->thirdparty->id) {
                            $tmpcompany->fetch($contactstatic->fk_soc);
                            if ($tmpcompany->id > 0) {
                                $contacttoshow .= $tmpcompany->name . ': ';
                            }
                        }
                    }
                    $contacttoshow .= $contactstatic->getFullName($outputlangs, 1);
                    $contacttoshow .= ' <' . ($contactstatic->email ?: $langs->transnoentitiesnoconv('NoEMail')) . '>';
                    $liste[$contact['id']] = $contacttoshow;
                }
            }
        }

        $formmail->withto              = $liste;
        $formmail->withtofree          = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1') : '1');
        $formmail->withtocc            = $liste;
        $formmail->withtoccc           = getDolGlobalString('MAIN_EMAIL_USECCC');
        $formmail->withtopic           = $outputlangs->trans('SendMailSubject', '__REF__');
        $formmail->withfile            = 2;
        $formmail->withbody            = 1;
        $formmail->withdeliveryreceipt = 1;
        $formmail->withcancel          = 1;

        // Array of substitutions.
        $formmail->substit = $substitutionarray;

        // Array of other parameters.
        $formmail->param['action']    = 'send';
        $formmail->param['models']    = 'saturne';
        $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
        $formmail->param['id']        = $object->id;
        $formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
        $formmail->param['fileinit']  = $files;

        // Show form.
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
