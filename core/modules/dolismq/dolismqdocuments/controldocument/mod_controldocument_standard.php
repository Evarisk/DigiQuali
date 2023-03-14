<?php
/* Copyright (C) 2022 EVARISK <technique@evarisk.com>
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
 *	\file       core/modules/dolismq/dolismqdocumets/controldocument/mod_controldocument_standard.php
 * \ingroup     dolismq
 *	\brief      File containing class for numbering module Standard
 */

/**
 * 	Class to manage controldocument numbering rules Standard
 */
class mod_controldocument_standard
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string document prefix
	 */
	public $prefix = 'FCD';

	/**
	 * @var string model name
	 */
	public $name = 'Dioné';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *  Returns the description of the numbering model
	 *
	 *  @return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("dolismq@dolismq");
		return $langs->trans('StandardModel', $this->prefix);
	}

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return string Example
	 */
	public function getExample(): string
	{
		return $this->prefix."0501-0001";
	}

	/**
	 * Return next free value
	 *
	 * @param  Object    $object Object we need next value for
	 * @return string            Value if KO, <0 if KO
	 * @throws Exception
	 */
	public function getNextValue(object $object)
	{
		global $db, $conf;

		// first we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."dolismq_dolismqdocuments";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		}

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			dol_syslog("mod_controldocument_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date = !empty($object->date_creation) ? $object->date_creation : dol_now();
		$yymm = strftime("%y%m", $date);

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04s", $max + 1);
		}

		dol_syslog("mod_controldocument_standard::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}

	/**
	 *	Returns version of numbering module
	 *
	 *	@return     string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load('admin');

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
