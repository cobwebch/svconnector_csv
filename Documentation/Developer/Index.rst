﻿.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: /Includes.rst.txt


.. _developer:

Developer's manual
------------------

Reading a flat file using the CSV connector service becomes a really
easy task. The first step is to get the proper service object with the desired parameters:

.. code-block:: php

   $parameters = [
      'filename' => 'path/to/your/file',
      'delimiter' => "\t",
      'text_qualifier' => '',
      'encoding' => 'utf-8',
      'skip_rows' => 1,
   ];
   $registry = GeneralUtility::makeInstance(\Cobweb\Svconnector\Registry\ConnectorRegistry::class);
   $connector = $registry->getServiceForType('csv');

The next step is simply to call the appropriate method from the API depending on which format you want to have
in return. For a PHP array:

.. code-block:: php

   $data = $connector->fetchArray($parameters);

In the above example we declare the file as using tabs as delimiter and no
text qualifier. Furthermore the file is declared as being encoded in
UTF-8 and its first line should be ignored.

Let's assume the file looks something like this:

.. code-block:: text

	last_name	first_name
	Wellington	Nicholas
	Oshiro	Ki
	Duncan	Dwayne

The resulting array stored in :code:`$data` will be:

+------------+-------------+
| last\_name | first\_name |
+============+=============+
| Wellington | Nicholas    |
+------------+-------------+
| Oshiro     | Ki          |
+------------+-------------+
| Duncan     | Dwayne      |
+------------+-------------+

The :code:`fetchRaw()` method returns a two-dimensional array with one
entry per line in the file and in each of these an entry per column.
The :code:`fetchXML()` method returns the array created by
:code:`fetchArray()` transformed to XML using
:code:`\TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs()`.
