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
 * \file    lib/dolismq_sheet.lib.php
 * \ingroup dolismq
 * \brief   Library files with common functions for Sheet.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare sheet pages header.
 *
 * @param  CommonObject $object Object.
 * @return array                Array of tabs.
 * @throws Exception
 */
function sheet_prepare_head(CommonObject $object): array
{
    return saturne_object_prepare_head($object);
}

/**
 * Get list of objects which can be linked to a sheet
 *
 * @param  CommonObject $object Object
 * @return array                Array of tabs
 * @throws Exception
 */
function get_sheet_linkable_objects(): array
{
	global $conf, $hookmanager, $db;

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

	require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

	$linkableObjectTypes = [];

	if (isModEnabled('product')) {
		$linkableObjectTypes['product'] = [
			'langs'      => 'ProductOrService',
			'picto'      => 'product',
			'className'  => 'Product',
			'post_name'  => 'fk_product',
			'link_name'  => 'product',
			'name_field' => 'ref',
			'create_url' => 'product/card.php',
			'class_path' => 'product/class/product.class.php',
		];
	}

	if (isModEnabled('productbatch')) {
		$linkableObjectTypes['productlot'] = [
			'langs'       => 'Batch',
			'picto'       => 'lot',
			'className'   => 'ProductLot',
			'post_name'   => 'fk_productlot',
			'link_name'   => 'productbatch',
			'name_field'  => 'batch',
			'fk_parent'   => 'fk_product',
			'parent_post' => 'fk_product',
			'create_url'  => 'product/stock/productlot_card.php',
			'class_path'  => 'product/stock/class/productlot.class.php',
		];
	}

	if (isModEnabled('user')) {
		$linkableObjectTypes['user'] = [
			'langs'      => 'User',
			'picto'      => 'user',
			'className'  => 'User',
			'post_name'  => 'fk_user',
			'link_name'  => 'user',
			'name_field' => 'lastname, firstname',
			'create_url' => 'user/card.php',
			'class_path' => 'user/class/user.class.php',
		];
	}

	if (isModEnabled('societe')) {
		$linkableObjectTypes['thirdparty'] = [
			'langs'      => 'ThirdParty',
			'picto'      => 'building',
			'className'  => 'Societe',
			'post_name'  => 'fk_soc',
			'link_name'  => 'societe',
			'name_field' => 'nom',
			'create_url' => 'societe/card.php',
			'class_path' => 'societe/class/societe.class.php',
		];
		$linkableObjectTypes['contact'] = [
			'langs'       => 'Contact',
			'picto'       => 'address',
			'className'   => 'Contact',
			'post_name'   => 'fk_contact',
			'link_name'   => 'contact',
			'name_field'  => 'lastname, firstname',
			'fk_parent'   => 'fk_soc',
			'parent_post' => 'fk_soc',
			'create_url'  => 'contact/card.php',
			'class_path'  => 'contact/class/contact.class.php',
		];
	}

	if (isModEnabled('project')) {
		$linkableObjectTypes['project'] = [
			'langs'      => 'Project',
			'picto'      => 'project',
			'className'  => 'Project',
			'post_name'  => 'fk_project',
			'link_name'  => 'project',
			'name_field' => 'ref, title',
			'create_url' => 'projet/card.php',
			'class_path' => 'projet/class/project.class.php',
		];
		$linkableObjectTypes['task'] = [
			'langs'       => 'Task',
			'picto'       => 'projecttask',
			'className'   => 'SaturneTask',
			'post_name'   => 'fk_task',
			'link_name'   => 'project_task',
			'name_field'  => 'label',
			'fk_parent'   => 'fk_projet',
			'parent_post' => 'fk_project',
			'create_url'  => 'projet/tasks.php',
			'class_path'  => 'projet/class/task.class.php',
		];
	}

	if (isModEnabled('facture')) {
		$linkableObjectTypes['invoice'] = [
			'langs'      => 'Invoice',
			'picto'      => 'bill',
			'className'  => 'Facture',
			'post_name'  => 'fk_invoice',
			'link_name'  => 'facture',
			'name_field' => 'ref',
			'create_url' => 'compta/facture/card.php',
			'class_path' => 'compta/facture/class/facture.class.php',
		];
	}

	if (isModEnabled('order')) {
		$linkableObjectTypes['order'] = [
			'langs'      => 'Order',
			'picto'      => 'order',
			'className'  => 'Commande',
			'post_name'  => 'fk_order',
			'link_name'  => 'commande',
			'name_field' => 'ref',
			'create_url' => 'commande/card.php',
			'class_path' => 'commande/class/commande.class.php',
		];
	}

	if (isModEnabled('contract')) {
		$linkableObjectTypes['contract'] = [
			'langs'      => 'Contract',
			'picto'      => 'contract',
			'className'  => 'Contrat',
			'post_name'  => 'fk_contract',
			'link_name'  => 'contrat',
			'name_field' => 'ref',
			'create_url' => 'contrat/card.php',
			'class_path' => 'contrat/class/contrat.class.php',
		];
	}

	if (isModEnabled('ticket')) {
		$linkableObjectTypes['ticket'] = [
			'langs'      => 'Ticket',
			'picto'      => 'ticket',
			'className'  => 'Ticket',
			'post_name'  => 'fk_ticket',
			'link_name'  => 'ticket',
			'name_field' => 'ref, subject',
			'create_url' => 'ticket/card.php',
			'class_path' => 'ticket/class/ticket.class.php',
		];
	}

//	if (isModEnabled('stock')) {
//		$linkableObjectTypes['entrepot'] = [
//			'langs'      => 'Warehouse',
//			'picto'      => 'stock',
//			'className'  => 'Entrepot',
//			'post_name'  => 'fk_entrepot',
//			'link_name'  => 'stock',
//			'name_field' => 'ref',
//			'create_url' => 'product/stock/entrepot/card.php',
//			'class_path' => 'product/stock/class/entrepot.class.php',
//		];
//	}

	//Hook to add controllable objects from other modules
	if ( ! is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}
	$hookmanager->initHooks(array('get_sheet_linkable_objects'));

	$reshook = $hookmanager->executeHooks('extendSheetLinkableObjectsList', $linkableObjectTypes);

	if ($reshook && (is_array($hookmanager->resArray) && !empty($hookmanager->resArray))) {
		$linkableObjectTypes = $hookmanager->resArray;
	}

	$linkableObjects = [];
	if (is_array($linkableObjectTypes) && !empty($linkableObjectTypes)) {
		foreach($linkableObjectTypes as $linkableObjectType => $linkableObjectInformations) {
			if ($linkableObjectType != 'context' && $linkableObjectType != 'currentcontext') {
				$confCode = 'DOLISMQ_SHEET_LINK_' . strtoupper($linkableObjectType);
				$linkableObjects[$linkableObjectType] = [
					'code'          => $confCode,
					'conf'          => $conf->global->$confCode,
					'name'          => 'Link' . ucfirst($linkableObjectType),
					'description'   => 'Link' . ucfirst($linkableObjectType) . 'Description',
					'langs'         => $linkableObjectInformations['langs'],
					'picto'         => $linkableObjectInformations['picto'],
					'className'     => $linkableObjectInformations['className'],
					'name_field'    => $linkableObjectInformations['name_field'],
					'post_name'     => $linkableObjectInformations['post_name'],
					'link_name'     => $linkableObjectInformations['link_name'],
					'fk_parent'     => $linkableObjectInformations['fk_parent'],
					'parent_post'   => $linkableObjectInformations['parent_post'],
					'create_url'    => $linkableObjectInformations['create_url'],
					'class_path'    => $linkableObjectInformations['class_path'],
				];
			}
		}
	}

	return $linkableObjects;
}
