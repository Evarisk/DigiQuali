<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/dolismq_function.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for DoliSMQ
 */

/**
 *	Return HTML code of the SELECT of list of all product_lotss (for a third party or all).
 *  This also set the number of product_lotss found into $this->num
 *
 * @since 9.0 Add afterSelectContactOptions hook
 *
 *	@param	int			$socid      	Id ot third party or 0 for all or -1 for empty list
 *	@param  array|int	$selected   	Array of ID of pre-selected product_lots id
 *	@param  string		$htmlname  	    Name of HTML field ('none' for a not editable field)
 *	@param  int			$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
 *	@param  string		$exclude        List of product_lotss id to exclude
 *	@param	string		$limitto		Disable answers that are not id in this array list
 *	@param	integer		$showfunction   Add function into label
 *	@param	string		$moreclass		Add more class to class style
 *	@param	bool		$options_only	Return options only (for ajax treatment)
 *	@param	integer		$showsoc	    Add company into label
 * 	@param	int			$forcecombo		Force to use combo box (so no ajax beautify effect)
 *  @param	array		$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/product_lotss.php',1), 'htmlname'=>'product_lotsid', 'params'=>array('add-customer-product_lots'=>'disabled')))
 *  @param	string		$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
 *  @param	string		$htmlid			Html id to use instead of htmlname
 *  @param	bool		$multiple		add [] in the name of element and add 'multiple' attribut
 *  @param	integer		$disableifempty Set tag 'disabled' on select if there is no choice
 *	@return	 int						<0 if KO, Nb of product_lots in list if OK
 */
function dolismq_select_product_lots($productid = -1, $selected = '', $htmlname = 'fk_productlot', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $moreparam = '', $htmlid = '', $multiple = false, $disableifempty = 0, $exclude_already_add = '')
{
	global $conf, $langs, $hookmanager, $action, $db;

	$langs->loadLangs(array("dolismQ@dolismq", "companies"));

	if (empty($htmlid)) $htmlid = $htmlname;
	$num                        = 0;

	if ($selected === '') $selected           = array();
	elseif ( ! is_array($selected)) $selected = array($selected);
	$out                                      = '';

	if ( ! is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}

	// We search third parties
	$sql                                                                                        = "SELECT pl.rowid, pl.fk_product, pl.batch";
	$sql                                                                                       .= " FROM " . MAIN_DB_PREFIX . "product_lot as pl";
	$sql .= " LEFT OUTER JOIN  " . MAIN_DB_PREFIX . "product as p ON p.rowid=pl.fk_product";
	$sql                                                                                       .= " WHERE pl.entity IN (" . getEntity('productlot') . ")";
	if ($productid > 0 || $productid == -1) $sql                                                       .= " AND pl.fk_product=" . $productid;
	$sql                                                                                       .= " ORDER BY pl.batch ASC";

	//dol_syslog(get_class($this)."::select_product_lotss", LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);

		if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlid, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
		}

		if ($htmlname != 'none' && ! $options_only) {
			$out .= '<select class="flat' . ($moreclass ? ' ' . $moreclass : '') . '" id="' . $htmlid . '" name="' . $htmlname . (($num || empty($disableifempty)) ? '' : ' disabled') . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . ' ' . ( ! empty($moreparam) ? $moreparam : '') . '>';
		}

		if (($showempty == 1 || ($showempty == 3 && $num > 1)) && ! $multiple) $out .= '<option value="0"' . (in_array(0, $selected) ? ' selected' : '') . '>&nbsp;</option>';
		if ($showempty == 2) $out                                                   .= '<option value="0"' . (in_array(0, $selected) ? ' selected' : '') . '>-- ' . $langs->trans("Internal") . ' --</option>';

		$i = 0;
		if ($num) {
			include_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
			$product_lotsstatic = new Productlot($db);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$product_lotsstatic->id     = $obj->rowid;
				$product_lotsstatic->batch  = $obj->batch;
				if (empty($outputmode)) {
					if (in_array($obj->rowid, $selected)) {
						$out .= '<option value="' . $obj->rowid . '" selected>' . $obj->batch . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $obj->batch . '</option>';
					}
				} else {
					array_push($outarray, array('key' => $obj->rowid, 'value' => $obj->batch, 'label' => $obj->batch));
				}

				$i++;
				if (($i % 10) == 0) $out .= "\n";
			}
		} else {
			$labeltoshow = ($productid != -1) ? ($langs->trans($productid ? "NoLotForThisProduct" : "NoLotDefined")) : $langs->trans('SelectAProductFirst');
			$out        .= '<option class="disabled" value="-1"' . (($showempty == 2 || $multiple) ? '' : ' selected') . ' disabled="disabled">';
			$out        .= $labeltoshow;
			$out        .= '</option>';
		}

		$parameters = array(
			'socid' => $productid,
			'htmlname' => $htmlname,
			'resql' => $resql,
			'out' => &$out,
			'showfunction' => $showfunction,
			'showsoc' => $showsoc,
		);

		//$reshook = $hookmanager->executeHooks('afterSelectContactOptions', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		if ($htmlname != 'none' && ! $options_only) {
			$out .= '</select>';
		}

		return $out;
	} else {
		dol_print_error($db);
		return -1;
	}
}
