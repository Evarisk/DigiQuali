<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
 *  \file			core/modules/dolismq/modules_dolismq.php
 *  \ingroup		dolismq
 *  \brief			File that contains parent class for dolismq numbering models
 */

/**
 *  Parent class to manage numbering of DoliSIRH
 */
abstract class ModeleNumRefDoliSMQ
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

    /**
     *	Return if a module can be used or not
     *
     *	@return boolean true if module can be used
     */
    public function isEnabled(): bool
    {
        return true;
    }

	/**
	 *	Returns the default description of the numbering template
	 *
	 *	@return string      Text with description
	 */
	public function info(): string
	{
		global $langs;
		$langs->load("dolismq@dolismq");
		return $langs->trans("NoDescription");
	}

    /**
     *  Checks if the numbers already in the database do not
     *  cause conflicts that would prevent this numbering working.
     *
     *  @param  Object  $object	Object we need next value for
     *  @return boolean     	false if conflicted, true if ok
     */
	public function canBeActivated(object $object): bool
	{
		return true;
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
		global $langs;
		$object->ref = '';
		return $langs->trans("NotAvailable");
	}

    /**
     *	Returns version of numbering module
     *
     *	@return string Value
     */
    public function getVersion(): string
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("VersionDevelopment");
        }
        if ($this->version == 'experimental') {
            return $langs->trans("VersionExperimental");
        }
        if ($this->version == 'dolibarr') {
            return DOL_VERSION;
        }
        if ($this->version) {
            return $this->version;
        }
        return $langs->trans("NotAvailable");
    }
}
