# DOLISMQ FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Informations

Version du module: 1.3.0

Dernière mise à jour: 21/06/2022

Prérequis:
* Dolibarr min version 14.0.0
* Dolibarr min version 15.0.2

Thème: Eldy Menu

Editeur/Licence: [Evarisk](https://www.evarisk.com) / GPL-v3

Assitance: [Forum www.dolibarr.fr](https://www.dolibarr.fr) / Par mail à contact@evarisk.com

Demo: [Demo Dolismq](https://www.demodoli.digirisk.com) - ID: demo - Password: demo

<!--
Documentation: [Wiki DoliSMQ](https://wiki.dolibarr.org/index.php/Module_DoliSMQ)

Forum: [Forum DoliSMQ]
-->

## Features

numero de module : 436301

<!--
![Screenshot dolismq](img/screenshot_dolismq.png?raw=true "DoliSMQ"){imgmd}
-->

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/dolismq```

```sh
cd ....../custom
git clone git@github.com:gitlogin/dolismq.git dolismq
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
