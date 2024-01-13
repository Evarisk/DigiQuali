<?php

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT DISTINCT ';
foreach ($object->fields as $key => $val) {
    if (!array_key_exists($key, $elementElementFields)) {
        $sql .= 't.' . $key . ', ';
    }
}

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
}
// Add fields from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
foreach($elementElementFields as $genericName => $elementElementName) {
    if (GETPOST('search_' . $genericName) > 0 || $fromtype == $elementElementName) {
        $id_tosearch = GETPOST('search' . $genericName) ?: $fromid;
        $sql .= ',' .  $elementElementName . '.fk_source, ';
    }
}
$sql = rtrim($sql, ', ');
if (array_key_exists($sortfield, $elementElementFields) && !preg_match('/' . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as ' . $elementElementFields[$sortfield] . '/', $sql)) {
    $sql .= ',' .  $elementElementFields[$sortfield] . '.fk_source';
}
$sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' as t';
if (!empty($conf->categorie->enabled)) {
	$sql .= Categorie::getFilterJoinQuery('survey', "t.rowid");
}
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";

foreach($elementElementFields as $genericName => $elementElementName) {
	if (GETPOST('search_'.$genericName) > 0 || $fromtype == $elementElementName) {
		$id_to_search = GETPOST('search_'.$genericName) ?: $fromid;
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as '. $elementElementName .' on ('. $elementElementName .'.fk_source = ' . $id_to_search . ' AND '. $elementElementName .'.sourcetype="'. $elementElementName .'" AND '. $elementElementName .'.targettype = "digiquali_survey")';
	}
}

if (array_key_exists($sortfield,$elementElementFields) && !preg_match('/' . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as '. $elementElementFields[$sortfield] .'/', $sql)) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as '. $elementElementFields[$sortfield] .' on ( '. $elementElementFields[$sortfield] .'.sourcetype="'. $elementElementFields[$sortfield] .'" AND '. $elementElementFields[$sortfield] .'.targettype = "digiquali_survey" AND '. $elementElementFields[$sortfield] .'.fk_target = t.rowid)';
}

// Add table from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
    $sql .= ' WHERE t.entity IN (' . getEntity($object->element) . ')';
} else {
    $sql .= ' WHERE 1 = 1';
}
$sql .= ' AND t.status >= ' . Survey::STATUS_DRAFT;

foreach($elementElementFields as $genericName => $elementElementName) {
    if (GETPOST('search_'.$genericName) > 0 || $fromtype == $elementElementName) {
        $sql .= ' AND t.rowid = ' . $elementElementName . '.fk_target ';
    }
}

foreach ($search as $key => $val) {
    if (!array_key_exists($key, $elementElementFields)) {
        if (array_key_exists($key, $object->fields)) {
            if ($key == 'status' && $val == -1) {
                continue;
            }
            $mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
            if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
                if ($val == '-1' || ($val === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
                    $val = '';
                }
                $mode_search = 2;
            }
            if ($val != '') {
                $sql .= natural_search('t.'. $db->escape($key), $val, (($key == 'status') ? 2 : $mode_search));
            }
        } elseif (preg_match('/(_dtstart|_dtend)$/', $key) && $val != '') {
            $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
            if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
                if (preg_match('/_dtstart$/', $key)) {
                    $sql .= ' AND t.' . $columnName . " >= '" . $db->idate($val) . "'";
                }
                if (preg_match('/_dtend$/', $key)) {
                    $sql .= ' AND t.' . $columnName . " <= '" . $db->idate($val) . "'";
                }
            }
        }
    }
}

if ($search_all) {
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

if (isModEnabled('categorie')) {
    $sql .= Categorie::getFilterSelectQuery('survey', 't.rowid', $search_category_array);
}

// Add where from extra fields
require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbTotalOfRecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    /* The fast and low memory method to get and count full list converts the sql into a sql count */
    $sqlForCount = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/', 'SELECT COUNT(*) as nbtotalofrecords FROM', $sql);
    $resql = $db->query($sqlForCount);
    if ($resql) {
        $objForCount      = $db->fetch_object($resql);
        $nbTotalOfRecords = $objForCount->nbtotalofrecords;
    } else {
        dol_print_error($db);
    }

    if (($page * $limit) > $nbTotalOfRecords) {    // if total of record found is smaller than page * limit, goto and load page 0
        $page   = 0;
        $offset = 0;
    }
    $db->free($resql);
}

// Complete request and execute it with limit
if (array_key_exists($sortfield, $elementElementFields)) {
    $sql .= ' ORDER BY '. $elementElementFields[$sortfield] . '.fk_source ' . $sortorder;
} else {
    $sql .= $db->order($sortfield, $sortorder);
}

if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
    $obj = $db->fetch_object($resql);
    $id  = $obj->rowid;
    header('Location: ' . dol_buildpath('/digiquali/view/survey/survey_card.php', 1) . '?id=' . $id);
    exit;
}

// Output page
// --------------------------------------------------------------------

$arrayofselected = is_array($toselect) ? $toselect : [];
$extraparams = $fromtype && $fromid ? '?fromtype=' . $fromtype . '&fromid=' . $fromid : '';

$param = $extraparams;
if (!empty($mode)) {
    $param .= '&mode=' . urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . urlencode($limit);
}
foreach ($search as $key => $val) {
    if (is_array($val) && count($val)) {
        foreach ($val as $skey) {
            $param .= '&search_' . $key . '[]=' . urlencode($skey);
        }
    } elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
        $param .= '&search_' . $key . 'month=' . ((int) GETPOST('search_' . $key . 'month', 'int'));
        $param .= '&search_' . $key . 'day=' . ((int) GETPOST('search_' . $key . 'day', 'int'));
        $param .= '&search_' . $key . 'year=' . ((int) GETPOST('search_' . $key . 'year', 'int'));
    } elseif ($val != '') {
        $param .= '&search_' . $key . '=' . urlencode($val);
    }
}
if ($optioncss != '') {
    $param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = ['prearchive' => '<span class="fas fa-archive paddingrightonly"></span>' . $langs->trans('Archive')];
if ($permissiontodelete) {
    $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans('Delete');
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, ['presend', 'predelete'])) {
    $arrayofmassactions = [];
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'. $_SERVER['PHP_SELF'] . $extraparams . '">';
if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="'. $optioncss . '">';
}
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="mode" value="' . $mode . '">';
if (GETPOSTISSET('id')) {
    print '<input type="hidden" name="id" value="' . GETPOST('id','int') . '">';
}
if (!empty($fromtype)) {
    $fromUrl = '&fromtype=' . $fromtype . '&fromid=' . $fromid;
}

$newcardbutton  = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), ['morecss' => 'reposition']);
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/([&?])*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), ['morecss' => 'reposition']);
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/digiquali/view/survey/survey_card.php', 1) . '?action=create' . $fromUrl, '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbTotalOfRecords, 'object_' . $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($massaction == 'prearchive') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassArchive'), $langs->trans('ConfirmMassArchivingQuestion', count($toselect)), 'archive', null, '', 0, 200, 500, 1);
}

// Add code for pre mass action (confirmation or email presend form)
$topicmail = 'SendSurveyRef';
$modelmail = 'survey';
$objecttmp = new Survey($db);
$trackid   = 'xxxx'.$object->id;
require DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
    $setupstring = '';
    foreach ($fieldstosearchall as $key => $val) {
        $fieldstosearchall[$key] = $langs->trans($val);
        $setupstring .= $key . '=' . $val . ';';
    }
    print '<!-- Search done like if PRODUCT_QUICKSEARCH_ON_FIELDS = ' . $setupstring . ' -->';
    print '<div class="divsearchfieldfilter">' . $langs->trans('FilterOnInto', $search_all) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter = '';

// Filter on categories
if (isModEnabled('categorie') && $user->rights->categorie->lire) {
    $formCategory = new FormCategory($db);
    $moreforfilter .= $formCategory->getFilterBox('survey', $search_category_array);
}

$parameters = [];
$resHook    = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($resHook)) {
    $moreforfilter .= $hookmanager->resPrint;
} else {
    $moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;

$signatoriesInDictionary = saturne_fetch_dictionary('c_' . $object->element . '_attendants_role');
if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
    $customFieldsPosition = 111;
    foreach ($signatoriesInDictionary as $signatoryInDictionary) {
        $arrayfields[$signatoryInDictionary->ref] = ['label' => $signatoryInDictionary->ref, 'checked' => 1, 'position' => $customFieldsPosition++, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx'];
    }
}

$arrayfields['SocietyAttendants'] = ['label' => 'SocietyAttendants', 'checked' => 1, 'position' => 115, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx'];

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$signatoriesInDictionary = saturne_fetch_dictionary('c_' . $object->element . '_attendants_role');
if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
    foreach ($signatoriesInDictionary as $signatoryInDictionary) {
        $object->fields['Custom'][$signatoryInDictionary->ref] = $arrayfields[$signatoryInDictionary->ref];
    }
}

$object->fields['Custom']['SocietyAttendants'] = $arrayfields['SocietyAttendants'];

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? ' listwithfilterbefore' : '') . '">';

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto = $form->showFilterButtons('left');
    print $searchpicto;
    print '</td>';
}
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'minwidth200', 1);
		}
		elseif ($key == 'fk_sheet') {
			print $sheet->selectSheetList(GETPOST('fromtype') == 'fk_sheet' ? GETPOST('fromid') : ($search['fk_sheet'] ?: 0), 'search_fk_sheet', 's.type = ' . '"' . $object->element . '"');
		}
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'minwidth100 maxwidth125 widthcentpercentminusxx', 1);
		} elseif (!preg_match('/^(date|timestamp)/', $val['type'])) {
			print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		}
		print '</td>';
	} elseif ($key == 'Custom') {
        foreach ($val as $resource) {
            if ($resource['checked']) {
                if ($resource['label'] == 'SocietyAttendants') {
                    print '<td class="liste_titre ' . $resource['css'] . '">';
                    //print $form->select_company($searchSocietyAttendants, 'search_society_attendants', '', 1);
                    print '</td>';
                } else {
                    print '<td class="liste_titre ' . $resource['css'] . '"></td>';
                }
            }
        }
    }
}

// Extra fields
require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
}
print '</tr>';

$totalarray            = [];
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}

$invertedElementElementFields = array_flip($elementElementFields);
foreach ($object->fields as $key => $val) {
    $disableSortField = dol_strlen($fromtype) > 0 ? preg_match('/'. $invertedElementElementFields[$fromtype] .'/',$key) : 0;

    $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
    if ($key == 'status') {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
    } elseif (in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
    }
    $cssforfield = preg_replace('/small\s*/', '', $cssforfield);    // the 'small' css must not be used for the title label
    if (!empty($arrayfields['t.'.$key]['checked'])) {
        print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : ''), $disableSortField);
        $totalarray['nbfield']++;
    } elseif ($key == 'Custom') {
        foreach ($val as $resource) {
            if ($resource['checked']) {
                print '<th class="wrapcolumntitle ' . $resource['css'] . ' liste_titre">';
                print $langs->trans($resource['label']);
                print '</th>';
            }
        }
    }
}

// Extra fields
require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray];
$hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print '</tr>';

// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
    foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
        if (preg_match('/\$object/', $val)) {
            $needToFetchEachLine++; // There is at least one compute field that use $object
        }
    }
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'] + 1;
$totalarray = [];
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);

$revertedElementFields = array_flip($elementElementFields);
$linkedObjects         = $object->fetchAllLinksForObjectType();

while ($i < $imaxinloop) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break; // Should not happen
    }

    // Store properties in $object
    $object->setVarsFromFetchObj($obj);

    $filter      = ['customsql' => 'fk_object = ' . $object->id . ' AND status > 0 AND object_type = "' . $object->element . '"'];
    $signatories = $signatory->fetchAll('', 'role', 0, 0, $filter);

    if ($mode == 'kanban') {
        if ($i == 0) {
            print '<tr><td colspan="' . $savnbfield . '">';
            print '<div class="box-flex-container">';
        }
        // Output Kanban
        print $object->getKanbanView();
        if ($i == ($imaxinloop - 1)) {
            print '</div>';
            print '</td></tr>';
        }
    } else {
        // Show here line of result
        print '<tr data-rowid="' . $object->id . '" class="oddeven">';
        // Action column
        if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
            print '<td class="nowrap center">';
            if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                $selected = 0;
                if (in_array($object->id, $arrayofselected)) {
                    $selected = 1;
                }
                print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"'. ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
        }
        foreach ($object->fields as $key => $val) {
            $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
            if (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            } elseif ($key == 'status') {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            }

            if (in_array($val['type'], ['timestamp'])) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
            } elseif ($key == 'ref') {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
            }

            if (in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && !in_array($key, ['rowid', 'status']) && empty($val['arrayofkeyval'])) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
            }
            if (!empty($arrayfields['t.' . $key]['checked'])) {
                print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '');
                if (preg_match('/tdoverflow/', $cssforfield)) {
                    print ' title="' . dol_escape_htmltag($object->$key) . '"';
                }
                print '>';
                if ($key == 'status') {
                    print $object->getLibStatut(5);
                } elseif ($key == 'fk_sheet') {
                    $sheet->fetch($object->fk_sheet);
                    print $sheet->getNomUrl(1);
                } elseif (in_array($key, $revertedElementFields)) {
                    $linkedElement = $linkNameElementCorrespondence[$elementElementFields[$key]];

                    if (is_array($linkedObjects[$obj->rowid]) && !empty($linkedElement['conf']) && (!empty($linkedObjects[$obj->rowid][$linkedElement['link_name']]))) {
                        $className    = $linkedElement['className'];
                        $linkedObject = new $className($db);

                        $linkedObjectType = $linkedElement['link_name'];
                        $linkedObjectId   = $linkedObjects[$obj->rowid][$linkedElement['link_name']];

                        if (!is_object($alreadyFetchedObjects[$linkedObjectType][$linkedObjectId])) {
                            $result = $linkedObject->fetch($linkedObjectId);
                        } else {
                            $linkedObject = $alreadyFetchedObjects[$linkedObjectType][$linkedObjectId];
                            $result = $linkedObjects[$obj->rowid][$linkedElement['link_name']];
                        }
                        if ($result > 0) {
                            $alreadyFetchedObjects[$linkedObjectType][$linkedObjectId] = $linkedObject;
                            print $linkedObject->getNomUrl(1);
                        }
                    }
                }
                else print $object->showOutputField($val, $key, $object->$key, '');
                print '</td>';
                if (!$i) $totalarray['nbfield']++;
                if (!empty($val['isameasure']))
                {
                    if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
                    $totalarray['val']['t.'.$key] += $object->$key;
                }
            } elseif ($key == 'Custom') {
                foreach ($val as $resource) {
                    if ($resource['checked']) {
                        if ($resource['label'] == 'SocietyAttendants') {
                            print '<td class="' . $resource['css'] . '">';
                            if (is_array($signatories) && !empty($signatories)) {
                                $alreadyAddedThirdParties = [];
                                foreach ($signatories as $objectSignatory) {
                                    if ($objectSignatory->element_type == 'socpeople') {
                                        $contact->fetch($objectSignatory->element_id);
                                        $thirdparty->fetch($contact->fk_soc);
                                        if (!in_array($thirdparty->id, $alreadyAddedThirdParties)) {
                                            print $thirdparty->getNomUrl(1);
                                            print '<br>';
                                        }
                                    } else {
                                        $userTmp->fetch($objectSignatory->element_id);
                                        if ($userTmp->contact_id > 0) {
                                            $contact->fetch($userTmp->contact_id);
                                            $thirdparty->fetch($contact->fk_soc);
                                            if (!in_array($thirdparty->id, $alreadyAddedThirdParties)) {
                                                print $thirdparty->getNomUrl(1);
                                                print '<br>';
                                            }
                                        }
                                    }
                                    $alreadyAddedThirdParties[] = $thirdparty->id;
                                }
                            }
                            print '</td>';
                        } else {
                            print '<td class="' . $resource['css'] . '">';
                            if (is_array($signatories) && !empty($signatories) && $signatories > 0) {
                                foreach ($signatories as $objectSignatory) {
                                    switch ($objectSignatory->attendance) {
                                        case 1:
                                            $cssButton = '#0d8aff';
                                            $userIcon  = 'fa-user-clock';
                                            break;
                                        case 2:
                                            $cssButton = '#e05353';
                                            $userIcon  = 'fa-user-slash';
                                            break;
                                        default:
                                            $cssButton = '#47e58e';
                                            $userIcon  = 'fa-user';
                                            break;
                                    }
                                    if ($objectSignatory->element_type == 'user' && $objectSignatory->role == $resource['label']) {
                                        $userTmp = $user;
                                        $userTmp->fetch($objectSignatory->element_id);
                                        print $userTmp->getNomUrl(1, '', 0, 0, 24, 1) . ' - ' . $objectSignatory->getLibStatut(3);
                                        print ' - <i class="fas ' . $userIcon . '" style="color: ' . $cssButton . '"></i>';
                                        print '<br>';
                                    } elseif ($objectSignatory->element_type == 'socpeople' && $objectSignatory->role == $resource['label']) {
                                        $contact->fetch($objectSignatory->element_id);
                                        print $contact->getNomUrl(1) . ' - ' . $objectSignatory->getLibStatut(3);
                                        print ' - <i class="fas ' . $userIcon . '" style="color: ' . $cssButton . '"></i>';
                                        print '<br>';
                                    }
                                }
                            }
                            print '</td>';
                        }
                    }
                }
            }
        }

        // Extra fields
        require DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

        // Fields from hook
        $parameters = ['arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
        $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Action column
        if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
            print '<td class="nowrap center">';
            if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                $selected = 0;
                if (in_array($object->id, $arrayofselected)) {
                    $selected = 1;
                }
                print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
        }
        if (!$i) {
            $totalarray['nbfield']++;
        }
        print '</tr>';
    }
    $i++;
}

// If no record found
if ($num == 0) {
    $colspan = 1;
    foreach ($arrayfields as $key => $val) {
        if (!empty($val['checked'])) {
            $colspan++;
        }
    }
    print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans('NoRecordFound') . '</td></tr>';
}

$db->free($resql);

$parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
$hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';
print '</form>';
