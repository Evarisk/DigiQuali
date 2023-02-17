<?php

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

require_once __DIR__.'/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/dolismq_function.lib.php';

$producttmp    = new Product($db);
$productlottmp = new Productlot($db);
$sheet         = new Sheet($db);
$usertmp       = new User($db);
$projecttmp    = new Project($db);
$tasktmp       = new Task($db);
$thirdparty    = new Societe($db);
$contact       = new Contact($db);
$formproject   = new FormProjets($db);

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT DISTINCT ';
foreach ($object->fields as $key => $val)
{
	if (!array_key_exists($key, $elementElementFields)) {
		$sql .= 't.' . $key . ', ';
	}
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (!empty($conf->categorie->enabled)) {
	$sql .= Categorie::getFilterJoinQuery('control', "t.rowid");
}
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";

foreach($elementElementFields as $genericName => $elementElementName) {
	if (GETPOST('search_'.$genericName) > 0 || $fromtype == $elementElementName) {
		$id_to_search = GETPOST('search_'.$genericName) ?: $fromid;
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as '. $elementElementName .' on ('. $elementElementName .'.fk_source = ' . $id_to_search . ' AND '. $elementElementName .'.sourcetype="'. $elementElementName .'" AND '. $elementElementName .'.targettype = "dolismq_control")';
	}
}

if (array_key_exists($sortfield,$elementElementFields)) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as '. $elementElementFields[$sortfield] .' on ( '. $elementElementFields[$sortfield] .'.sourcetype="'. $elementElementFields[$sortfield] .'" AND '. $elementElementFields[$sortfield] .'.targettype = "dolismq_control" AND '. $elementElementFields[$sortfield] .'.fk_target = t.rowid)';
}

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($object->element).")";
else $sql .= " WHERE 1 = 1".$sqlfilter;

foreach($elementElementFields as $genericName => $elementElementName) {
	if (GETPOST('search_'.$genericName) > 0 || $fromtype == $elementElementName) {
		$sql .= ' AND t.rowid = '. $elementElementName .'.fk_target ';
	}
}

foreach ($search as $key => $val) {
	if (!array_key_exists($key, $elementElementFields)) {
		if (array_key_exists($key, $object->fields)) {
			if ($key == 'status' && $search[$key] == -1) {
				continue;
			}
			if ($key == 'verdict' && $search[$key] == 0) {
				continue;
			}
			$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
			if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
				if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
					$search[$key] = '';
				}
				$mode_search = 2;
			}
			if ($search[$key] != '') {
				$sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
			}
		} else {
			if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
				$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
				if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
					if (preg_match('/_dtstart$/', $key)) {
						$sql .= " AND t.".$columnName." >= '".$db->idate($search[$key])."'";
					}
					if (preg_match('/_dtend$/', $key)) {
						$sql .= " AND t." . $columnName . " <= '" . $db->idate($search[$key]) . "'";
					}
				}
			}
		}
	}
}

if ($searchAll) $sql .= natural_search(array_keys($fieldstosearchall), $searchAll);

if (!empty($conf->categorie->enabled)) {
	$sql .= Categorie::getFilterSelectQuery('control', "t.rowid", $search_category_array);
}

//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if (array_key_exists($sortfield, $elementElementFields)) {
	$sql .= ' ORDER BY '. $elementElementFields[$sortfield] .'.fk_source ' . $sortorder;
} else {
	$sql .= $db->order($sortfield, $sortorder);
}
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
	$num = $nbtotalofrecords;
} else {
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $searchAll && !$page)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/dolismq/view/control/control_card.php', 1).'?id='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

$arrayofselected = is_array($toselect) ? $toselect : array();
$extraparams = $fromtype && $fromid ? '?fromtype=' . $fromtype . '&fromid=' . $fromid : '';

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
$param .= $fromtype && $fromid ? '&fromtype=' . $fromtype . '&fromid=' . $fromid : '';
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach ($search as $key => $val)
{
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
);
if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].$extraparams.'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
if (GETPOSTISSET('id')) {
	print '<input type="hidden" name="id" value="'.GETPOST('id','int').'">';
}
if (!empty($fromtype)) {
	$fromurl = '&fromtype='.$fromtype.'&fromid='.$fromid;
}
$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/dolismq/view/control/control_card.php', 1).'?action=create'.$fromurl, '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendControlRef";
$modelmail = "control";
$objecttmp = new Control($db);
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($searchAll)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $searchAll).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// Filter on categories
if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox('control', $search_category_array);
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
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
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
		elseif ($key == 'fk_sheet') {
			print $sheet->selectSheetList(GETPOST('fromtype') == 'fk_sheet' ? GETPOST('fromid') : ($search['fk_sheet'] ?: 0), 'search_fk_sheet');
		}
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth125', 1);
		} elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';

		print '</td>';
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";

// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val)
	{
		if (preg_match('/\$object/', $val)) $needToFetchEachLine++; // There is at least one compute field that use $object
	}
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
while ($i < ($limit ? min($num, $limit) : $num))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	// Store properties in $object
	$object->setVarsFromFetchObj($obj);

	// Show here line of result
	print '<tr class="oddeven">';
	foreach ($object->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
		elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status'))) $cssforfield .= ($cssforfield ? ' ' : '').'right';
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
			if ($key == 'status') {
				print $object->getLibStatut(5);
			}
			elseif ($key == 'ref') print $object->getNomUrl(1);
			elseif ($key == 'fk_product') {
				$object->fetchObjectLinked('', 'product','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCT) && (!empty($object->linkedObjectsIds['product']))) {
					$producttmp->fetch(array_shift($object->linkedObjectsIds['product']));
					if ($producttmp > 0) {
						print $producttmp->getNomUrl(1);
					}
				}
			}
			elseif ($key == 'fk_lot') {
				$object->fetchObjectLinked('', 'productbatch','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PRODUCTLOT) && (!empty($object->linkedObjectsIds['productbatch']))) {
					$productlottmp->fetch(array_shift($object->linkedObjectsIds['productbatch']));
					if ($productlottmp > 0) {
						print $productlottmp->getNomUrl(1);
					}
				}
			}
			elseif ($key == 'fk_user') {
				$object->fetchObjectLinked('', 'user','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_USER) && (!empty($object->linkedObjectsIds['user']))) {
					$usertmp->fetch(array_shift($object->linkedObjectsIds['user']));
					if ($usertmp > 0) {
						print $usertmp->getNomUrl(1);
					}
				}
			}
			elseif ($key == 'fk_thirdparty') {
				$object->fetchObjectLinked('', 'societe','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_THIRDPARTY) && (!empty($object->linkedObjectsIds['societe']))) {
					$thirdparty->fetch(array_shift($object->linkedObjectsIds['societe']));
					if ($thirdparty > 0) {
						print $thirdparty->getNomUrl(1);
					}
				}
			}
			elseif ($key == 'fk_contact') {
				$object->fetchObjectLinked('', 'contact','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_CONTACT) && (!empty($object->linkedObjectsIds['contact']))) {
					$contact->fetch(array_shift($object->linkedObjectsIds['contact']));
					if ($contact > 0) {
						print $contact->getNomUrl(1);
					}
				}
			}
			elseif ($key == 'fk_project') {
				$object->fetchObjectLinked('', 'project','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_PROJECT) && (!empty($object->linkedObjectsIds['project']))) {
					$projecttmp->fetch(array_shift($object->linkedObjectsIds['project']));
					if ($projecttmp > 0) {
						print $projecttmp->getNomUrl(1, '', 1);
					}
				}
			}
			elseif ($key == 'fk_task') {
				$object->fetchObjectLinked('', 'project_task','', 'dolismq_control');
				if (!empty($conf->global->DOLISMQ_CONTROL_SHOW_TASK) && (!empty($object->linkedObjectsIds['project_task']))) {
					$tasktmp->fetch(array_shift($object->linkedObjectsIds['project_task']));
					if ($tasktmp > 0) {
						print $tasktmp->getNomUrl(1, 'withproject');
					}
				}
			}
			elseif ($key == 'fk_sheet') {
				$sheet->fetch($object->fk_sheet);
				print $sheet->getNomUrl(1);
			}
			else print $object->showOutputField($val, $key, $object->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure']))
			{
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}
	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>'."\n";

	$i++;
}

// If no record found
if ($num == 0)
{
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";
