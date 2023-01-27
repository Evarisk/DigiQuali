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
 */

/**
 *	\file       dolismqindex.php
 *	\ingroup    dolismq
 *	\brief      Home page of dolismq top menu
 */

// Load Dolibarr environment
if (file_exists("../saturne/saturne.main.inc.php")) $res = @include "../saturne/saturne.main.inc.php";

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/parsedown/Parsedown.php';

require_once __DIR__ . '/core/modules/modDoliSMQ.class.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(['dolismq@dolismq']);

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$dolismq = new modDoliSMQ($db);
$parse   = new Parsedown();

// Security check
if (!$user->rights->dolismq->lire) accessforbidden();

/*
 *  Actions
*/

if ($action == 'closenotice') {
	dolibarr_set_const($db, "DoliSMQ_SHOW_PATCH_NOTE", 0, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$help_url = 'FR:Module_DoliSMQ';
$title    = $langs->trans('DoliSMQArea');
$morejs   = ['/dolismq/js/dolismq.js'];
$morecss  = ['/dolismq/css/dolismq.css'];

saturne_header('dolismq', $action,'',0,'', $title . ' ' . $dolismq->version, $help_url, '', 0, 0, $morejs, $morecss);

print load_fiche_titre($title . ' ' . $dolismq->version, '', 'dolismq_color.png@dolismq');

if ($conf->global->DOLISMQ_JUST_UPDATED == 1) : ?>
	<div class="wpeo-notice notice-success">
		<div class="notice-content">
			<div class="notice-subtitle"><strong><?php echo $langs->trans("DoliSMQUpdate"); ?></strong>
				<?php echo $langs->trans('DoliSMQHasBeenUpdatedTo', $dolismq->version) ?>
			</div>
		</div>
	</div>
	<?php dolibarr_set_const($db, 'DOLISMQ_JUST_UPDATED', 0, 'integer', 0, '', $conf->entity);
endif;

if ($conf->global->DOLISMQ_VERSION != $dolismq->version) {
	$dolismq->remove();
	$dolismq->init();

	dolibarr_set_const($db, 'DOLISMQ_JUST_UPDATED', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DOLISMQ_SHOW_PATCH_NOTE', 1, 'integer', 0, '', $conf->entity);
}

if ($conf->global->DOLISMQ_SHOW_PATCH_NOTE) : ?>
	<div class="wpeo-notice notice notice-info">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans("DoliSMQPatchNote", $dolismq->version); ?>
				<div class="show-patchnote wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('ShowPatchNote'); ?>">
					<input hidden class="modal-to-open" value="patch-note">
					<i class="fas fa-list button-icon"></i>
				</div>
			</div>
		</div>
		<div class="notice-close notice-close-forever wpeo-tooltip-event" aria-label="<?php echo $langs->trans("DontShowPatchNote"); ?>" data-direction="left"><i class="fas fa-times"></i></div>
	</div>

	<div class="wpeo-modal wpeo-modal-patchnote" id="patch-note">
		<div class="modal-container wpeo-modal-event" style="max-width: 1280px; max-height: 1000px">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans("DoliSMQPatchNote", $dolismq->version);  ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>
			<!-- Modal Content-->
			<div class="modal-content">
				<?php $ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/evarisk/dolismq/releases/tags/' . $dolismq->version);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT,'DoliSMQ');
				$output  = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($output);
				$data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
				$data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
				$html = $parse->text($data->body);
				print $html;
				?>
			</div>
			<!-- Modal-Footer -->
			<div class="modal-footer">
				<div class="wpeo-button button-grey button-uppercase modal-close">
					<span><?php echo $langs->trans('CloseModal'); ?></span>
				</div>
			</div>
		</div>
	</div>
<?php endif;
// End of page
llxFooter();
$db->close();
