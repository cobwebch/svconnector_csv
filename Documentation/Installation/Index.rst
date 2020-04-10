.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _installation:

Installation
------------

Install this extension and you can start using its API for reading
flat files inside your own code. It requires extension “svconnector”
which provides the base for all connector services.

André Wuttig has developed a variant for reading large files line per line.
If this sounds like something you may need, please take a look at
https://github.com/portrino/svconnector_csv_extended


.. _installation-update-240:

Updating to version 2.4.0
^^^^^^^^^^^^^^^^^^^^^^^^^

The "encoding" :ref:`configuration property <configuration>` has change behavior.
It used to accept all known encoding values plus all the synonyms defined in array
:php:`\TYPO3\CMS\Core\Charset\CharsetConverter::$synonyms`. This array does not
exist in TYPO3 v10 anymore, thus usage of synonyms has been dropped. Check
your configuration and verify that you use encoding names as defined in
https://www.php.net/manual/en/mbstring.supported-encodings.php.
