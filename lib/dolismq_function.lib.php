<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *  Show photos of an object (nbmax maximum), into several columns
 *
 *  @param		string	$modulepart		'product', 'ticket', ...
 *  @param      string	$sdir        	Directory to scan (full absolute path)
 *  @param      int		$size        	0=original size, 1='small' use thumbnail if possible
 *  @param      int		$nbmax       	Nombre maximum de photos (0=pas de max)
 *  @param      int		$nbbyrow     	Number of image per line or -1 to use div. Used only if size=1.
 * 	@param		int		$showfilename	1=Show filename
 * 	@param		int		$showaction		1=Show icon with action links (resize, delete)
 * 	@param		int		$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
 * 	@param		int		$maxWidth		Max width of original image when size='small'
 *  @param      int     $nolink         Do not add a href link to view enlarged imaged into a new tab
 *  @param      int     $notitle        Do not add title tag on image
 *  @param		int		$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 *  @return     string					Html code to show photo. Number of photos shown is saved in this->nbphoto
 */
function dolismq_show_photos($modulepart, $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "", $object = null)
{
	global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	if (is_object($object)) {
		$dir  = $sdir . '/' . $object->ref . '/';
		$pdir = $subdir . '/' . $object->ref . '/';
	} else {
		$dir  = $sdir . '/';
		$pdir = $subdir . '/';
	}

	// Defined relative dir to DOL_DATA_ROOT
	$relativedir = '';
	if ($dir) {
		$relativedir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
	}

	$dirthumb  = $dir . 'thumbs/';
	$pdirthumb = $pdir . 'thumbs/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		foreach ($filearray as $key => $val) {
			$photo = '';
			$file  = $val['name'];

			//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

			//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
			if (image_format_supported($file) >= 0) {
				$nbphoto++;
				$photo        = $file;
				$viewfilename = $file;

				if ($size == 1 || $size == 'small') {   // Format vignette
					// Find name of thumb file
					$photo_vignette = basename(getImageFileNameForSize($dir . $file, '_small'));

					if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

					// Get filesize of original file
					$imgarray = dol_getImageSize($dir . $photo);

					if ($nbbyrow > 0) {
						if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

						if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
						$return                               .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%" class="photo">';
					} elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

					$return .= "\n";

					$relativefile = preg_replace('/^\//', '', $pdir . $photo);
					if (empty($nolink)) {
						$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
						if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
						else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
					}

					// Show image (width height=$maxHeight)
					// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
					$alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
					$alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
					if ($notitle) $alt = '';

					if ($usesharelink) {
						if ($val['share']) {
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
								$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
								$return .= '<img class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" width="' . $maxWidth . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" width="' . $maxWidth . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							}
						} else {
							$return .= '<!-- Show nophoto file (because file is not shared) -->';
							$return .= '<img class="photo photowithmargin" height="' . $maxHeight . '" width="' . $maxWidth . '" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . dol_escape_htmltag($alt) . '">';
						}
					} else {
						if ($photo_vignette && $imgarray['height'] > $maxHeight) {
							$return .= '<!-- Show thumb -->';
							$return .= '<img class="photo clicked-photo-preview ' . (($file == $object->photo && $object->element == 'dolismqelement') ? 'favorite-photo' : '') . '"  height="' . $maxHeight . '" width="' . $maxWidth . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '">';
						} else {
							$return .= '<!-- Show original file -->';
							$return .= '<img class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" width="' . $maxWidth . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '">';
						}
					}
					$return .= '<input type="hidden" class="filename" value="' . $photo . '">';

					if (empty($nolink)) $return .= '</a>';
					$return                     .= "\n";
					if ($showfilename) $return  .= '<br>' . $viewfilename;
					if ($showaction) {
						$return .= '<br>';
						// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
						if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
							$return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
						}
					}
					$return .= "\n";

					if ($nbbyrow > 0) {
						$return                                 .= '</td>';
						if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
					} elseif ($nbbyrow < 0) $return .= '</div>';
				}

				if (empty($size)) {     // Format origine
					$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';

					if ($showfilename) $return .= '<br>' . $viewfilename;
				}

				// On continue ou on arrete de boucler ?
				if ($nbmax && $nbphoto >= $nbmax) break;
			}
		}

		if ($size == 1 || $size == 'small') {
			if ($nbbyrow > 0) {
				// Ferme tableau
				while ($nbphoto % $nbbyrow) {
					$return .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	}
	if (is_object($object)) {
		$object->nbphoto = $nbphoto;
	}
	return $return;
}

/**
 *      Return a string to show the box with list of available documents for object.
 *      This also set the property $this->numoffiles
 *
 *      @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule:nameofsubmodule', 'mymodule_temp', ...)
 *      @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 *      @param      string				$filedir            Directory to scan
 *      @param      string				$urlsource          Url of origin page (for return)
 *      @param      int|string[]        $genallowed         Generation is allowed (1/0 or array list of templates)
 *      @param      int					$delallowed         Remove is allowed (1/0)
 *      @param      string				$modelselected      Model to preselect by default
 *      @param      integer				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
 *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
 *      @param      int					$iconPDF            Deprecated, see getDocumentsLink
 * 		@param		int					$notused	        Not used
 * 		@param		integer				$noform				Do not output html form tags
 * 		@param		string				$param				More param on http links
 * 		@param		string				$title				Title to show on top of form. Example: '' (Default to "Documents") or 'none'
 * 		@param		string				$buttonlabel		Label on submit button
 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
 * 		@param		string				$morepicto			Add more HTML content into cell with picto
 *      @param      Object              $object             Object when method is called from an object card.
 *      @param		int					$hideifempty		Hide section of generated files if there is no file
 *      @param      string              $removeaction       (optional) The action to remove a file
 *      @param      int                 $active             (optional) To show gen button disabled
 *      @param      string              $tooltiptext       (optional) Tooltip text when gen button disabled
 * 		@return		string              					Output string with HTML array of documents (might be empty string)
 */
function dolismqshowdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null, $hideifempty = 0, $removeaction = 'remove_file', $active = 1, $tooltiptext = '')
{
	global $db, $langs, $conf, $user, $hookmanager, $form;

	if ( ! is_object($form)) $form = new Form($db);

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	// Add entity in $param if not already exists
	if ( ! preg_match('/entity\=[0-9]+/', $param)) {
		$param .= ($param ? '&' : '') . 'entity=' . ( ! empty($object->entity) ? $object->entity : $conf->entity);
	}

	$hookmanager->initHooks(array('formfile'));

	// Get list of files
	$file_list = null;
	if ( ! empty($filedir)) {
		$file_list = dol_dir_list($filedir, 'files', 0, '(\.odt|\.zip|\.pdf)', '', 'date', SORT_DESC, 1);
	}
	if ($hideifempty && empty($file_list)) return '';

	$out         = '';
	$forname     = 'builddoc';
	$headershown = 0;
	$showempty   = 0;

	$out .= "\n" . '<!-- Start show_document -->' . "\n";

	$titletoshow                       = $langs->trans("Documents");
	if ( ! empty($title)) $titletoshow = ($title == 'none' ? '' : $title);

	// Show table
	if ($genallowed) {
		$submodulepart = $modulepart;
		// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
		$tmp = explode(':', $modulepart);
		if ( ! empty($tmp[1])) {
			$modulepart    = $tmp[0];
			$submodulepart = $tmp[1];
		}

		// For normalized external modules.
		$file = dol_buildpath('/' . $modulepart . '/core/modules/' . $modulepart . '/' . strtolower($submodulepart) . '/modules_' . strtolower($submodulepart) . '.php', 0);
		include_once $file;

		$class = 'ModeleODT' . $submodulepart;

		if (class_exists($class)) {
			if (preg_match('/specimen/', $param)) {
				$type      = strtolower($class) . 'specimen';
				$modellist = array();

				include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
				$modellist = getListOfModels($db, $type, 0);
			} else {
				$modellist = call_user_func($class . '::liste_modeles', $db, 100);
			}
		} else {
			dol_print_error($db, "Bad value for modulepart '" . $modulepart . "' in showdocuments");
			return -1;
		}

		// Set headershown to avoid to have table opened a second time later
		$headershown = 1;

		if (empty($buttonlabel)) $buttonlabel = $langs->trans('Generate');

		if ($conf->browser->layout == 'phone') $urlsource .= '#' . $forname . '_form'; // So we switch to form after a generation
		if (empty($noform)) $out                          .= '<form action="' . $urlsource . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc') . '" id="' . $forname . '_form" method="post">';
		$out                                              .= '<input type="hidden" name="action" value="builddoc">';
		$out                                              .= '<input type="hidden" name="token" value="' . newToken() . '">';

		$out .= load_fiche_titre($titletoshow, '', '');
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="liste formdoc noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		$addcolumforpicto = ($delallowed || $morepicto);
		$colspan          = (3 + ($addcolumforpicto ? 1 : 0)); $colspanmore = 0;

		$out .= '<th colspan="' . $colspan . '" class="formdoc liste_titre maxwidthonsmartphone center">';
		// Model
		if ( ! empty($modellist)) {
			asort($modellist);
			$out      .= '<span class="hideonsmartphone">' . $langs->trans('Model') . ' </span>';
			$modellist = array_filter($modellist, 'remove_index');
			if (is_array($modellist) && count($modellist) == 1) {    // If there is only one element
				$arraykeys                = array_keys($modellist);
				$arrayvalues              = preg_replace('/template_/', '', array_values($modellist)[0]);
				$modellist[$arraykeys[0]] = $arrayvalues;
				$modelselected            = $arraykeys[0];
			}
			$morecss                                        = 'maxwidth200';
			if ($conf->browser->layout == 'phone') $morecss = 'maxwidth100';
			$out                                           .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);

			if ($conf->use_javascript_ajax) {
				$out .= ajax_combobox('model');
			}
		} else {
			$out .= '<div class="float">' . $langs->trans("Files") . '</div>';
		}

		// Button
		if ($active) {
			$genbutton  = '<input class="button buttongen" id="' . $forname . '_generatebutton" name="' . $forname . '_generatebutton"';
			$genbutton .= ' type="submit" value="' . $buttonlabel . '"';
		} else {
			$genbutton  = '<input class="button buttongen disabled" name="' . $forname . '_generatebutton" style="cursor: not-allowed"';
			$genbutton .= '  value="' . $buttonlabel . '"';
		}

		if ( ! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
		$genbutton                                                                         .= '>';
		if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
			$langs->load("errors");
			$genbutton .= ' ' . img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
		}
		if ( ! $allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
		if (empty($modellist) && ! $showempty && $modulepart != 'unpaid') $genbutton                                                                      = '';
		$out                                                                                                                                             .= $genbutton;
		if ( ! $active) {
			$htmltooltip  = '';
			$htmltooltip .= $tooltiptext;

			$out .= '<span class="center">';
			$out .= $form->textwithpicto($langs->trans('Help'), $htmltooltip, 1, 0);
			$out .= '</span>';
		}

		$out .= '</th>';

		if ( ! empty($hookmanager->hooks['formfile'])) {
			foreach ($hookmanager->hooks['formfile'] as $module) {
				if (method_exists($module, 'formBuilddocLineOptions')) {
					$colspanmore++;
					$out .= '<th></th>';
				}
			}
		}
		$out .= '</tr>';

		// Execute hooks
		$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart);
		if (is_object($hookmanager)) {
			$reshook = $hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
			$out    .= $hookmanager->resPrint;
		}
	}

	// Get list of files
	if ( ! empty($filedir)) {
		$link_list = array();
		if (is_object($object) && $object->id > 0) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
			$link      = new Link($db);
			$sortfield = $sortorder = null;
			$res       = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
		}

		$out .= '<!-- html.formfile::showdocuments -->' . "\n";

		// Show title of array if not already shown
		if (( ! empty($file_list) || ! empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
			&& ! $headershown) {
			$headershown = 1;
			$out        .= '<div class="titre">' . $titletoshow . '</div>' . "\n";
			$out        .= '<div class="div-table-responsive-no-min">';
			$out        .= '<table class="noborder centpercent" id="' . $modulepart . '_table">' . "\n";
		}

		// Loop on each file found
		if (is_array($file_list)) {
			foreach ($file_list as $file) {
				// Define relative path for download link (depends on module)
				$relativepath                    = $file["name"]; // Cas general
				if ($modulesubdir) $relativepath = $modulesubdir . "/" . $file["name"]; // Cas propal, facture...

				$out .= '<tr class="oddeven">';

				$documenturl                                                      = DOL_URL_ROOT . '/document.php';
				if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

				// Show file name with link to download
				$out .= '<td class="minwidth200">';
				$out .= '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . ($param ? '&' . $param : '') . '"';

				$mime                                  = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
				$out                                  .= '>';
				$out                                  .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]);
				$out                                  .= dol_trunc($file["name"], 150);
				$out                                  .= '</a>' . "\n";
				$out                                  .= '</td>';

				// Show file size
				$size = ( ! empty($file['size']) ? $file['size'] : dol_filesize($filedir . "/" . $file["name"]));
				$out .= '<td class="nowrap right">' . dol_print_size($size, 1, 1) . '</td>';

				// Show file date
				$date = ( ! empty($file['date']) ? $file['date'] : dol_filemtime($filedir . "/" . $file["name"]));
				$out .= '<td class="nowrap right">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

				if ($delallowed || $morepicto) {
					$out .= '<td class="right nowraponall">';
					if ($delallowed) {
						$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
						$out         .= '<a href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=' . $removeaction . '&amp;file=' . urlencode($relativepath);
						$out         .= ($param ? '&amp;' . $param : '');
						$out         .= '">' . img_picto($langs->trans("Delete"), 'delete') . '</a>';
					}
					if ($morepicto) {
						$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
						$out      .= $morepicto;
					}
					$out .= '</td>';
				}

				if (is_object($hookmanager)) {
					$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath);
					$res        = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
					if (empty($res)) {
						$out .= $hookmanager->resPrint; // Complete line
						$out .= '</tr>';
					} else {
						$out = $hookmanager->resPrint; // Replace all $out
					}
				}
			}
		}
		// Loop on each link found
		//      if (is_array($link_list))
		//      {
		//          $colspan = 2;
		//
		//          foreach ($link_list as $file)
		//          {
		//              $out .= '<tr class="oddeven">';
		//              $out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
		//              $out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank">';
		//              $out .= $file->label;
		//              $out .= '</a>';
		//              $out .= '</td>';
		//              $out .= '<td class="right">';
		//              $out .= dol_print_date($file->datea, 'dayhour');
		//              $out .= '</td>';
		//              if ($delallowed || $printer || $morepicto) $out .= '<td></td>';
		//              $out .= '</tr>'."\n";
		//          }
		//      }

		if (count($file_list) == 0 && count($link_list) == 0 && $headershown) {
			$out .= '<tr><td colspan="' . (3 + ($addcolumforpicto ? 1 : 0)) . '" class="opacitymedium">' . $langs->trans("None") . '</td></tr>' . "\n";
		}
	}

	if ($headershown) {
		// Affiche pied du tableau
		$out .= "</table>\n";
		$out .= "</div>\n";
		if ($genallowed) {
			if (empty($noform)) $out .= '</form>' . "\n";
		}
	}
	$out .= '<!-- End show_document -->' . "\n";

	return $out;
}

/**
 *	Exclude index.php files from list of models for document generation
 *
 * @param   string $model
 * @return  '' or $model
 */
function remove_index($model)
{
	if (preg_match('/index.php/', $model)) {
		return '';
	} else {
		return $model;
	}
}

/**
*  Return a link to the user card (with optionaly the picto)
*  Use this->id,this->lastname, this->firstname
*
*  @param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
*  @param	string	$option						On what the link point to ('leave', 'nolink', )
*  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
*  @param	integer	$notooltip					1=Disable tooltip on picto and name
*  @param	int		$maxlen						Max length of visible user name
*  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
*  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
*  @param  string  $morecss            		Add more css on link
*  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
*  @return	string								String with URL
*/
function getNomUrl($withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1, $object = null, $display_initials = 1)
{
	global $langs, $conf, $db, $hookmanager, $user;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $menumanager;

	   if ( ! $object->rights->user->user->lire && $object->id != $object->id) $option = 'nolink';

	if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg = 0;

	$result = ''; $label = '';

	if ( ! empty($object->photo)) {
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('userphoto', $object, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
		$label .= '</div><div style="clear: both;"></div>';
	}

	// Info Login
	$label                               .= '<div class="centpercent">';
	$label                               .= '<u>' . $langs->trans("User") . '</u><br>';
	$label                               .= '<b>' . $langs->trans('Name') . ':</b> ' . $object->getFullName($langs, '');
	if ( ! empty($object->login)) $label .= '<br><b>' . $langs->trans('Login') . ':</b> ' . $object->login;
	if ( ! empty($object->job)) $label   .= '<br><b>' . $langs->trans("Job") . ':</b> ' . $object->job;
	$label                               .= '<br><b>' . $langs->trans("Email") . ':</b> ' . $object->email;
	if ( ! empty($object->phone)) $label .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $object->phone;
	if ( ! empty($object->admin))
		$label                           .= '<br><b>' . $langs->trans("Administrator") . '</b>: ' . yn($object->admin);
	if ( ! empty($object->socid)) {	// Add thirdparty for external users
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($object->socid);
		if (empty($hidethirdpartylogo)) $companylink = ' ' . $thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
		$company                                     = ' (' . $langs->trans("Company") . ': ' . $thirdpartystatic->name . ')';
	}
	$type   = ($object->socid ? $langs->trans("External") . $company : $langs->trans("Internal"));
	$label .= '<br><b>' . $langs->trans("Type") . ':</b> ' . $type;
	$label .= '<br><b>' . $langs->trans("Status") . '</b>: ' . $object->getLibStatut(4);
	$label .= '</div>';
	if ($infologin > 0) {
		$label                                                        .= '<br>';
		$label                                                        .= '<br><u>' . $langs->trans("Session") . '</u>';
		$label                                                        .= '<br><b>' . $langs->trans("IPAddress") . '</b>: ' . $_SERVER["REMOTE_ADDR"];
		if ( ! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label .= '<br><b>' . $langs->trans("ConnectedOnMultiCompany") . ':</b> ' . $conf->entity . ' (user entity ' . $object->entity . ')';
		$label                                                        .= '<br><b>' . $langs->trans("AuthenticationMode") . ':</b> ' . $_SESSION["dol_authmode"] . (empty($dolibarr_main_demo) ? '' : ' (demo)');
		$label                                                        .= '<br><b>' . $langs->trans("ConnectedSince") . ':</b> ' . dol_print_date($object->datelastlogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("PreviousConnexion") . ':</b> ' . dol_print_date($object->datepreviouslogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("CurrentTheme") . ':</b> ' . $conf->theme;
		$label                                                        .= '<br><b>' . $langs->trans("CurrentMenuManager") . ':</b> ' . $menumanager->name;
		$s                                                             = picto_from_langcode($langs->getDefaultLang());
		$label                                                        .= '<br><b>' . $langs->trans("CurrentUserLanguage") . ':</b> ' . ($s ? $s . ' ' : '') . $langs->getDefaultLang();
		$label                                                        .= '<br><b>' . $langs->trans("Browser") . ':</b> ' . $conf->browser->name . ($conf->browser->version ? ' ' . $conf->browser->version : '') . ' (' . $_SERVER['HTTP_USER_AGENT'] . ')';
		$label                                                        .= '<br><b>' . $langs->trans("Layout") . ':</b> ' . $conf->browser->layout;
		$label                                                        .= '<br><b>' . $langs->trans("Screen") . ':</b> ' . $_SESSION['dol_screenwidth'] . ' x ' . $_SESSION['dol_screenheight'];
		if ($conf->browser->layout == 'phone') $label                 .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $langs->trans("Yes");
		if ( ! empty($_SESSION["disablemodules"])) $label             .= '<br><b>' . $langs->trans("DisabledModules") . ':</b> <br>' . join(', ', explode(',', $_SESSION["disablemodules"]));
	}
	if ($infologin < 0) $label = '';

	$url                         = DOL_URL_ROOT . '/user/card.php?id=' . $object->id;
	if ($option == 'leave') $url = DOL_URL_ROOT . '/holiday/list.php?id=' . $object->id;

	if ($option != 'nolink') {
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
	}

	$linkclose = "";
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}
	$linkstart = '<a href="' . $url . '"';
	if (empty($notooltip)) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$langs->load("users");
			$label      = $langs->trans("ShowUser");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';

		/*
		 $hookmanager->initHooks(array('userdao'));
		 $parameters=array('id'=>$object->id);
		 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
		 */
	}

	$linkstart .= $linkclose . '>';
	$linkend    = '</a>';

	//if ($withpictoimg == -1) $result.='<div class="nowrap">';
	$result .= (($option == 'nolink') ? '' : $linkstart);
	if ($withpictoimg) {
		$paddafterimage                              = '';
		if (abs($withpictoimg) == 1) $paddafterimage = 'style="margin-' . ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right') . ': 3px;"';
		// Only picto
		if ($withpictoimg > 0) $picto = '<!-- picto user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '">' . img_object('', 'user', $paddafterimage . ' ' . ($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1) . '</span>';
		// Picto must be a photo
		else $picto = '<!-- picto photo user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '"' . ($paddafterimage ? ' ' . $paddafterimage : '') . '>' . Form::showphoto('userphoto', $object, 0, 0, 0, 'userphoto' . ($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1) . '</span>';
		$result    .= $picto;
	}

	if ($withpictoimg > -2 && $withpictoimg != 2 && $display_initials) {
		$initials = '';
		if (dol_strlen($object->firstname)) {
			$initials .= str_split($object->firstname, 1)[0];
		}
		if (dol_strlen($object->lastname)) {
			$initials .= str_split($object->lastname, 1)[0];
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '<span class=" nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		if ($mode == 'login') $result                                  .= $initials;
		else $result                                                   .= $initials;
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '</span>';
	} elseif ($display_initials == 0) {
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '<span class="nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		}
		if ($mode == 'login') {
			$result .= dol_string_nohtmltag(dol_trunc($object->login, $maxlen));
		} else {
			$result .= dol_string_nohtmltag($object->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '</span>';
		}
	}
	$result .= (($option == 'nolink') ? '' : $linkend);
	//if ($withpictoimg == -1) $result.='</div>';

	$result .= $companylink;

	global $action;
	$hookmanager->initHooks(array('userdao'));
	$parameters               = array('id' => $object->id, 'getnomurl' => $result);
	$reshook                  = $hookmanager->executeHooks('getNomUrl', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result             .= $hookmanager->resPrint;

	return $result;
}

function show_category_image($object, $upload_dir)
{

	global $langs;
	$nbphoto = 0;
	$nbbyrow = 5;

	$maxWidth  = 160;
	$maxHeight = 120;

	$pdir = get_exdir($object->id, 2, 0, 0, $object, 'category') . $object->id . "/photos/";
	$dir  = $upload_dir . '/' . $pdir;

	$listofphoto = $object->liste_photos($dir);
	if (is_array($listofphoto) && count($listofphoto)) {
		//      print '<br>';
		//      print '<table width="100%" valign="top" align="center">';

		foreach ($listofphoto as $key => $obj) {
			$nbphoto++;

			//          if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
			//          if ($nbbyrow) print '<td width="'.ceil(100 / $nbbyrow).'%" class="">';


			// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
			if ($obj['photo_vignette']) {
				$filename = $obj['photo_vignette'];
			} else {
				$filename = $obj['photo'];
			}

			// Nom affiche
			$viewfilename = $obj['photo'];

			// Taille de l'image
			$object->get_image_size($dir . $filename);
			$imgWidth  = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
			$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

			print '<img border="0" width="' . $imgWidth . '" height="' . $imgHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=category&entity=' . $object->entity . '&file=' . urlencode($pdir . $filename) . '">';

			//          if ($nbbyrow) print '</td>';
			//          if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';
		}

		// Ferme tableau
		while ($nbphoto % $nbbyrow) {
			$nbphoto++;
		}

		//      print '</table>';
	}

	if ($nbphoto < 1) {
		print '<div class="opacitymedium">' . $langs->trans("NoPhotoYet") . "</div>";
	}
}

function dolismq_show_medias($modulepart = 'ecm', $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 80, $maxWidth = 80, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "")
{
	global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';
	$pdir      = $subdir . '/';


	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$j         = 0;

	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		foreach ($filearray as $key => $val) {
			$file = $val['name'];

			if (image_format_supported($file) >= 0) {
				$nbphoto++;

				if ($size == 1 || $size == 'small') {   // Format vignette
					$relativepath = 'dolismq/medias/thumbs';
					$modulepart   = 'ecm';
					$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);

					$filename = preg_split('/\./',  $val['name']);
					$filename = $filename[0].'_'.$size.'.'.$filename[1];

					?>

					<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>" element="risk-evaluation">
						<figure class="photo-image">
							<?php
							$urladvanced = getAdvancedPreviewUrl($modulepart, 'dolismq/medias/' .$val['name'], 0, 'entity=' . $conf->entity); ?>
							<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>"><i class="fas fa-2x fa-search-plus"></i></a>
							<?php if (image_format_supported($val['name']) >= 0) : ?>
								<?php $fullpath = $path . '/' . $filename . '&entity=' . $conf->entity; ?>
							<input class="filename" type="hidden" value="<?php echo $val['name']; ?>">
							<img class="photo photo<?php echo $j ?>" height="<?php echo $maxHeight; ?>" width="<?php echo $maxWidth; ?>" src="<?php echo $fullpath; ?>">
							<?php endif; ?>
						</figure>
						<div class="title"><?php echo $val['name']; ?></div>
					</div><?php
					$j++;
				}
			}
		}
	}

	return $return;
}

function dolismq_show_medias_linked($modulepart = 'ecm', $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "", $object = null, $favorite = '', $show_favorite_button = 1, $show_unlink_button = 1)
{
		global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	$dir  = $sdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');
	$pdir = $subdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');

	// Defined relative dir to DOL_DATA_ROOT
	$relativedir = '';
	if ($dir) {
		$relativedir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
	}

	$dirthumb  = $dir . 'thumbs/';
	$pdirthumb = $pdir . 'thumbs/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		foreach ($filearray as $key => $val) {
			$return .= '<td class="media-container">';
			$photo   = '';
			$file    = $val['name'];

			//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

			//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
			if (image_format_supported($file) >= 0) {
				$nbphoto++;
				$photo        = $file;
				$viewfilename = $file;

				if ($size == 1 || $size == 'small') {   // Format vignette
					// Find name of thumb file
					$photo_vignette                                                  = basename(getImageFileNameForSize($dir . $file, '_small'));
					if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

					// Get filesize of original file
					$imgarray = dol_getImageSize($dir . $photo);

					if ($nbbyrow > 0) {
						if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

						if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
						$return                               .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%" class="photo">';
					} elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

					$return .= "\n";

					$relativefile = preg_replace('/^\//', '', $pdir . $photo);
					if (empty($nolink)) {
						$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
						if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
						else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
					}

					// Show image (width height=$maxHeight)
					// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
					$alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
					$alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
					if ($notitle) $alt = '';
					if ($usesharelink) {
						if ($val['share']) {
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
								$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
								$return .= '<img width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img  width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							}
						} else {
							$return .= '<!-- Show nophoto file (because file is not shared) -->';
							$return .= '<img  width="65" height="65" class="photo photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . dol_escape_htmltag($alt) . '">';
						}
					} else {
						if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
							$return .= '<!-- Show thumb -->';
							$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo clicked-photo-preview"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '">';
						} else {
							$return .= '<!-- Show original file -->';
							$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo photowithmargin  clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '">';
						}
					}

					if (empty($nolink)) $return .= '</a>';
					$return                     .= "\n";
					if ($showfilename) $return  .= '<br>' . $viewfilename;
					if ($showaction) {
						$return .= '<br>';
						// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
						if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
							$return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
						}
					}
					$return .= "\n";

					if ($nbbyrow > 0) {
						$return                                 .= '</td>';
						if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
					} elseif ($nbbyrow < 0) $return .= '</td>';
				}

				if (empty($size)) {     // Format origine
					$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';

					if ($showfilename) $return .= '<br>' . $viewfilename;
				}

				// On continue ou on arrete de boucler ?
				if ($nbmax && $nbphoto >= $nbmax) break;
			}

			//$return .= '<div>';

			if ($show_favorite_button) {
				$return .= '
				<div class="wpeo-button button-square-50 button-blue media-gallery-favorite" value="' . $object->id . '">
					<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
					<input class="filename" type="hidden" value="' . $photo . '">
					<i class="' . ($favorite == $photo ? 'fas' : ($object->photo == $photo ? 'fas' : 'far')) . ' fa-star button-icon"></i>
				</div>';
			}
			if ($show_unlink_button) {
				$return .= '
				<div class="wpeo-button button-square-50 button-grey media-gallery-unlink" value="' . $object->id . '">
				<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
				<input class="filename" type="hidden" value="' . $photo . '">
				<i class="fas fa-unlink button-icon"></i>
				</div>';
			}
		}
		$return .= "</td>\n";

		if ($size == 1 || $size == 'small') {
			if ($nbbyrow > 0) {
				// Ferme tableau
				while ($nbphoto % $nbbyrow) {
					$return .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	} else {
		$return .= '<td class="media-container">';

		print $langs->trans('NoMediaLinked');
		print '</td>';
	}
	if (is_object($object)) {
		$object->nbphoto = $nbphoto;
	}
	return $return;
}

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
function dolismq_select_product_lots($productid, $selected = '', $htmlname = 'fk_lot', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $moreparam = '', $htmlid = '', $multiple = false, $disableifempty = 0, $exclude_already_add = '')
{
	global $conf, $langs, $hookmanager, $action, $db;

	$langs->loadLangs(array("dolisqm@dolisqm", "companies"));

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

