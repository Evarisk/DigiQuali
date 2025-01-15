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
 * \file    lib/digiquali_sheet.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Sheet.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare array of tabs for sheet.
 *
 * @param  Sheet $object Sheet object.
 * @return array         Array of tabs.
 * @throws Exception
 */
function sheet_prepare_head(Sheet $object): array
{
    // Global variables definitions
    global $conf, $langs;

    $head[2][0] = dol_buildpath('/digiquali/view/sheet/sheet_export.php', 1) . '?id=' . $object->id;
    $head[2][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-export pictofixedwidth"></i>' . $langs->trans('Export') : '<i class="fas fa-file-export"></i>';
    $head[2][2] = 'export';

    return saturne_object_prepare_head($object, $head);
}

/**
 * Get list of objects which can be linked to a sheet
 *
 * @return array     Array of sheet linkable objects
 * @throws Exception
 */
function get_sheet_linkable_objects(): array
{
	global $conf, $db, $hookmanager, $langs;

	//To add an object :

	//	'langs'         => Object translation
	//	'langfile'      => File lang translation
	//	'picto'         => Object picto for img_picto() function (equals $this->picto)
	//	'className'     => Class name
	//	'name_field'    => Object name to be shown (ref, label, firstname, etc.)
	//	'post_name'     => Name of post sent retrieved by GETPOST() function
	//	'link_name'     => Name of object sourcetype in llx_element_element, special case for task we need to write module_task
	//	'tab_type'      => Tab type element for prepare_head function
	//	'fk_parent'     => OPTIONAL : Name of parent for objects as productlot, contact, task
	//	'parent_post'   => OPTIONAL : Name of parent post (retrieved by GETPOST() function, it can be different from fk_parent
	//	'create_url'    => Path to creation card, no need to add "?action=create"
	//	'class_path'    => Path to object class

	$linkableObjectTypes = [];

	if (isModEnabled('product')) {
		$linkableObjectTypes['product'] = [
			'langs'          => 'ProductOrService',
            'langfile'       => 'products',
			'picto'          => 'product',
			'className'      => 'Product',
			'post_name'      => 'fk_product',
			'link_name'      => 'product',
            'tab_type'       => 'product',
            'hook_name_list' => 'productservicelist',
            'hook_name_card' => 'productcard',
			'name_field'     => 'ref',
			'create_url'     => 'product/card.php',
			'class_path'     => 'product/class/product.class.php',
		];
	}

	if (isModEnabled('productbatch')) {
		$linkableObjectTypes['productlot'] = [
			'langs'          => 'Batch',
            'langfile'       => 'products',
			'picto'          => 'lot',
			'className'      => 'ProductLot',
			'post_name'      => 'fk_productlot',
			'link_name'      => 'productbatch',
            'tab_type'       => 'productlot',
            'hook_name_list' => 'product_lotlist',
            'hook_name_card' => 'productlotcard',
			'name_field'     => 'batch',
			'fk_parent'      => 'fk_product',
			'parent_post'    => 'fk_product',
			'create_url'     => 'product/stock/productlot_card.php',
			'class_path'     => 'product/stock/class/productlot.class.php',
		];
	}

	if (isModEnabled('user')) {
		$linkableObjectTypes['user'] = [
			'langs'          => 'User',
			'picto'          => 'user',
			'className'      => 'User',
			'post_name'      => 'fk_user',
			'link_name'      => 'user',
            'tab_type'       => 'user',
            'hook_name_list' => 'userlist',
            'hook_name_card' => 'usercard',
			'name_field'     => 'lastname, firstname',
			'create_url'     => 'user/card.php',
			'class_path'     => 'user/class/user.class.php',
		];
	}

	if (isModEnabled('societe')) {
		$linkableObjectTypes['thirdparty'] = [
			'langs'          => 'ThirdParty',
            'langfile'       => 'companies',
			'picto'          => 'building',
			'className'      => 'Societe',
			'post_name'      => 'fk_soc',
			'link_name'      => 'societe',
            'tab_type'       => 'thirdparty',
            'hook_name_list' => 'thirdpartylist',
            'hook_name_card' => 'thirdpartycard',
			'name_field'     => 'nom',
			'create_url'     => 'societe/card.php',
			'class_path'     => 'societe/class/societe.class.php',
		];
		$linkableObjectTypes['contact'] = [
			'langs'          => 'Contact',
            'langfile'       => 'companies',
			'picto'          => 'address',
			'className'      => 'Contact',
			'post_name'      => 'fk_contact',
			'link_name'      => 'contact',
            'tab_type'       => 'contact',
            'hook_name_list' => 'contactlist',
            'hook_name_card' => 'contactcard',
			'name_field'     => 'lastname, firstname',
			'fk_parent'      => 'fk_soc',
			'parent_post'    => 'fk_soc',
			'create_url'     => 'contact/card.php',
			'class_path'     => 'contact/class/contact.class.php',
		];
	}

	if (isModEnabled('project')) {
		$linkableObjectTypes['project'] = [
			'langs'          => 'Project',
            'langfile'       => 'projects',
			'picto'          => 'project',
			'className'      => 'Project',
			'post_name'      => 'fk_project',
			'link_name'      => 'project',
            'tab_type'       => 'project',
            'hook_name_list' => 'projectlist',
            'hook_name_card' => 'projectcard',
			'name_field'     => 'ref, title',
			'create_url'     => 'projet/card.php',
			'class_path'     => 'projet/class/project.class.php',
		];
		$linkableObjectTypes['task'] = [
			'langs'          => 'Task',
            'langfile'       => 'projects',
			'picto'          => 'projecttask',
			'className'      => 'SaturneTask',
			'post_name'      => 'fk_task',
			'link_name'      => 'project_task',
            'tab_type'       => 'task',
            'hook_name_list' => 'tasklist',
            'hook_name_card' => 'projecttaskcard',
			'name_field'     => 'label',
			'fk_parent'      => 'fk_projet',
			'parent_post'    => 'fk_project',
			'create_url'     => 'projet/tasks.php',
			'class_path'     => 'custom/saturne/class/task/saturnetask.class.php',
		];
	}

	if (isModEnabled('facture')) {
		$linkableObjectTypes['invoice'] = [
			'langs'          => 'Invoice',
            'langfile'       => 'bills',
			'picto'          => 'bill',
			'className'      => 'Facture',
			'post_name'      => 'fk_invoice',
			'link_name'      => 'facture',
            'tab_type'       => 'invoice',
            'hook_name_list' => 'invoicelist',
            'hook_name_card' => 'invoicecard',
			'name_field'     => 'ref',
			'create_url'     => 'compta/facture/card.php',
			'class_path'     => 'compta/facture/class/facture.class.php',
		];
	}

	if (isModEnabled('order')) {
		$linkableObjectTypes['order'] = [
			'langs'          => 'Order',
            'langfile'       => 'orders',
			'picto'          => 'order',
			'className'      => 'Commande',
			'post_name'      => 'fk_order',
			'link_name'      => 'commande',
            'tab_type'       => 'order',
            'hook_name_list' => 'orderlist',
            'hook_name_card' => 'ordercard',
			'name_field'     => 'ref',
			'create_url'     => 'commande/card.php',
			'class_path'     => 'commande/class/commande.class.php',
		];
	}

	if (isModEnabled('contract')) {
		$linkableObjectTypes['contract'] = [
			'langs'          => 'Contract',
            'langfile'       => 'contracts',
			'picto'          => 'contract',
			'className'      => 'Contrat',
			'post_name'      => 'fk_contract',
			'link_name'      => 'contrat',
            'tab_type'       => 'contract',
            'hook_name_list' => 'contractlist',
            'hook_name_card' => 'contractcard',
			'name_field'     => 'ref',
			'create_url'     => 'contrat/card.php',
			'class_path'     => 'contrat/class/contrat.class.php',
		];
	}

	if (isModEnabled('ticket')) {
		$linkableObjectTypes['ticket'] = [
			'langs'          => 'Ticket',
			'picto'          => 'ticket',
			'className'      => 'Ticket',
			'post_name'      => 'fk_ticket',
			'link_name'      => 'ticket',
            'tab_type'       => 'ticket',
            'hook_name_list' => 'ticketlist',
            'hook_name_card' => 'ticketcard',
			'name_field'     => 'ref, subject',
			'create_url'     => 'ticket/card.php',
			'class_path'     => 'ticket/class/ticket.class.php',
		];
	}

    if (isModEnabled('stock')) {
        $linkableObjectTypes['entrepot'] = [
            'langs'          => 'Warehouse',
            'langfile'       => 'stocks',
            'picto'          => 'stock',
            'className'      => 'Entrepot',
            'post_name'      => 'fk_entrepot',
            'link_name'      => 'stock',
            'tab_type'       => 'stock',
            'hook_name_list' => 'stocklist',
            'hook_name_card' => 'warehousecard',
            'name_field'     => 'ref',
            'create_url'     => 'product/stock/entrepot/card.php',
            'class_path'     => 'product/stock/class/entrepot.class.php',
        ];

        $linkableObjectTypes['inventory'] = [
            'langs'          => 'Inventories',
            'langfile'       => 'stocks',
            'picto'          => 'inventory',
            'className'      => 'Inventory',
            'post_name'      => 'fk_inventory',
            'link_name'      => 'stock_inventory',
            'tab_type'       => 'inventory',
            'name_field'     => 'ref',
            'hook_name_card' => 'inventorycard',
            'hook_name_list' => 'inventorylist',
            'create_url'     => 'product/inventory/card.php',
            'class_path'     => 'product/inventory/class/inventory.class.php',
        ];

        $linkableObjectTypes['mouvement'] = [
            'langs'          => 'Movements',
            'langfile'       => 'stocks',
            'picto'          => 'movement',
            'className'      => 'MouvementStock',
            'post_name'      => 'fk_stock_mouvement',
            'link_name'      => 'stock_mouvement',
            'tab_type'       => '',
            'table_element'  => 'stock_mouvement',
            'name_field'     => 'id',
            'hook_name_card' => '',
            'hook_name_list' => 'movementlist',
            'create_url'     => '',
            'class_path'     => 'product/stock/class/mouvementstock.class.php',
        ];
    }

    if (isModEnabled('expedition')) {
        $linkableObjectTypes['expedition'] = [
            'langs'          => 'Shipment',
            'langfile'       => 'sendings',
            'picto'          => 'dolly',
            'className'      => 'DigiQualiExpedition',
            'post_name'      => 'fk_expedition',
            'link_name'      => 'expedition',
            'tab_type'       => 'delivery',
            'hook_name_list' => 'shipmentlist',
            'hook_name_card' => 'ordershipmentcard',
            'name_field'     => 'ref',
            'class_path'     => 'custom/digiquali/class/dolibarrobjects/digiqualiexpedition.class.php',
        ];
    }

    if (isModEnabled('propal')) {
        $linkableObjectTypes['propal'] = [
            'langs'          => 'Proposal',
            'langfile'       => 'propal',
            'picto'          => 'propal',
            'className'      => 'Propal',
            'post_name'      => 'fk_propal',
            'link_name'      => 'propal',
			'tab_type'       => 'propal',
			'hook_name_list' => 'propallist',
			'hook_name_card' => 'propalcard',
			'name_field'     => 'ref',
            'create_url'     => 'comm/propal/card.php',
            'class_path'     => 'comm/propal/class/propal.class.php',
        ];
    }

    if (isModEnabled('mrp')) {
        $linkableObjectTypes['bom'] = [
            'langs'          => 'MenuBOM',
            'langfile'       => 'mrp',
            'picto'          => 'bom',
            'className'      => 'BOM',
            'post_name'      => 'fk_bom',
            'link_name'      => 'bom',
            'tab_type'       => 'bom@mrp',
            'name_field'     => 'ref',
            'hook_name_card' => 'bomcard',
            'hook_name_list' => 'bomlist',
            'create_url'     => 'bom/bom_card.php',
            'class_path'     => 'bom/class/bom.class.php',
        ];

        $linkableObjectTypes['mrp'] = [
            'langs'          => 'Mrp',
            'langfile'       => 'mrp',
            'picto'          => 'mrp',
            'className'      => 'Mo',
            'post_name'      => 'fk_mo',
            'link_name'      => 'mo',
            'tab_type'       => 'mo@mrp',
            'name_field'     => 'ref',
            'hook_name_card' => 'mocard',
            'hook_name_list' => 'molist',
            'create_url'     => 'mrp/mo_card.php',
            'class_path'     => 'mrp/class/mo.class.php',
        ];
    }

    if (isModEnabled('reception')) {
        $linkableObjectTypes['reception'] = [
            'langs'          => 'Receptions',
            'langfile'       => 'receptions',
            'picto'          => 'dollyrevert',
            'className'      => 'Reception',
            'post_name'      => 'fk_reception',
            'link_name'      => 'reception',
            'tab_type'       => 'reception',
            'name_field'     => 'ref_supplier',
            'hook_name_card' => 'receptioncard',
            'hook_name_list' => 'receptionlist',
            'create_url'     => 'reception/card.php',
            'class_path'     => 'reception/class/reception.class.php',
        ];
    }

    if (isModEnabled('ficheinter')) {
        $linkableObjectTypes['ficheinter'] = [
            'langs'          => 'Interventions',
            'langfile'       => 'interventions',
            'picto'          => 'intervention',
            'className'     => 'Fichinter',
            'post_name'      => 'fk_fichinter',
            'link_name'      => 'fichinter',
            'tab_type'       => 'intervention',
            'name_field'     => 'ref',
            'hook_name_card' => 'interventioncard',
            'hook_name_list' => 'interventionlist',
            'create_url'     => 'fichinter/card.php',
            'class_path'     => 'fichinter/class/fichinter.class.php',
        ];
    }

//    if (isModEnabled('supplier_proposal')) {
//        $linkableObjectTypes['supplier_proposal'] = [
//            'langs'      => 'SupplierProposalShort',
//            'langfile'   => 'supplier_proposal',
//            'picto'      => 'supplier_proposal',
//            'className'  => 'DigiQualiSupplierProposal',
//            'post_name'  => 'fk_supplier_proposal',
//            'link_name'  => 'supplier_proposal',
//            'tab_type'   => 'supplier_proposal',
//            'name_field' => 'ref',
//            'create_url' => 'supplier_proposal/card.php',
//            'class_path' => 'custom/digiquali/class/dolibarrobjects/digiqualisupplierproposal.class.php',
//        ];
//    }
//
//    if (isModEnabled('fournisseur')) {
//        $linkableObjectTypes['supplier_order'] = [
//            'langs'      => 'SupplierOrder',
//            'langfile'   => 'orders',
//            'picto'      => 'supplier_order',
//            'className'  => 'CommandeFournisseur',
//            'post_name'  => 'fk_supplier_order',
//            'link_name'  => 'commande_fournisseur',
//            'tab_type'   => 'supplier_order',
//            'name_field' => 'ref',
//            'create_url' => 'fourn/commande/card.php',
//            'class_path' => 'fourn/class/fournisseur.commande.class.php',
//        ];
//        $linkableObjectTypes['supplier_invoice'] = [
//            'langs'      => 'SupplierInvoice',
//            'langfile'   => 'bills',
//            'picto'      => 'supplier_invoice',
//            'className'  => 'FactureFournisseur',
//            'post_name'  => 'fk_supplier_invoice',
//            'link_name'  => 'facture_fournisseur',
//            'tab_type'   => 'supplier_invoice',
//            'name_field' => 'ref',
//            'create_url' => 'fourn/facture/card.php',
//            'class_path' => 'fourn/class/fournisseur.facture.class.php',
//        ];
//    }

    // Hook to add controllable objects from other modules
	if ( ! is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}
	$hookmanager->initHooks(['get_sheet_linkable_objects']);

	$resHook = $hookmanager->executeHooks('extendSheetLinkableObjectsList', $linkableObjectTypes);

	if (empty($resHook) && is_array($hookmanager->resArray) && !empty($hookmanager->resArray)) {
		$linkableObjectTypes = $hookmanager->resArray;
	}

	$linkableObjects = [];
	if (is_array($linkableObjectTypes) && !empty($linkableObjectTypes)) {
		foreach($linkableObjectTypes as $linkableObjectType => $linkableObjectInformations) {
			if ($linkableObjectType != 'context' && $linkableObjectType != 'currentcontext') {
                require_once DOL_DOCUMENT_ROOT . '/' . $linkableObjectInformations['class_path'];

				$confCode = 'DIGIQUALI_SHEET_LINK_' . strtoupper($linkableObjectType);
				$linkableObjects[$linkableObjectType] = [
					'code'           => $confCode,
					'conf'           => $conf->global->$confCode,
					'name'           => 'Link' . ucfirst($linkableObjectType),
					'description'    => 'Link' . ucfirst($linkableObjectType) . 'Description',
					'langs'          => $linkableObjectInformations['langs'] ?? '',
					'langfile'       => $linkableObjectInformations['langfile'] ?? '',
					'picto'          => $linkableObjectInformations['picto'] ?? '',
					'className'      => $linkableObjectInformations['className'] ?? '',
					'name_field'     => $linkableObjectInformations['name_field'] ?? '',
					'post_name'      => $linkableObjectInformations['post_name'] ?? '',
					'link_name'      => $linkableObjectInformations['link_name'] ?? '',
					'tab_type'       => $linkableObjectInformations['tab_type'] ?? '',
					'hook_name_list' => $linkableObjectInformations['hook_name_list'] ?? '',
					'hook_name_card' => $linkableObjectInformations['hook_name_card'] ?? '',
					'fk_parent'      => $linkableObjectInformations['fk_parent'] ?? '',
					'parent_post'    => $linkableObjectInformations['parent_post'] ?? '',
					'create_url'     => $linkableObjectInformations['create_url'] ?? '',
					'class_path'     => $linkableObjectInformations['class_path'] ?? '',
				];
                if (!empty($linkableObjectInformations['langfile'])) {
                    $langs->load($linkableObjectInformations['langfile']);
                }
            }
        }
	}

	return $linkableObjects;
}
