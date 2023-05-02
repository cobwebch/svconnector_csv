.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Developer's manual
------------------

Reading a flat file using the CSV connector service becomes a really
easy task. The first step is to get the proper service object:

.. code-block:: php

   $registry = GeneralUtility::makeInstance(\Cobweb\Svconnector\Registry\ConnectorRegistry::class);
   $connector = $registry->getServiceForType('csv');

An additional step could be to check if the service is indeed available,
by calling :php:`$connector->isAvailable()`, although - in this particular
case - the CSV connector service is always available.

The next step is simply to call the appropriate method from the API –
with the right parameters – depending on which format you want to have
in return. For a PHP array:

.. code-block:: php

	$parameters = [
		'filename' => 'path/to/your/file',
		'delimiter' => “\t”,
		'text_qualifier' => '',
		'encoding' => 'utf-8',
		'skip_rows' => 1,
	];
	$data = $connector->fetchArray($parameters);

In this example we declare the file as using tabs as delimiter and no
text qualifier. Furthermore the file is declared as being encoded in
UTF-8 and its first line should be ignored.

Let's assume the file looks something like this:

.. code-block:: text

	last_name	first_name
	Wellington	Nicholas
	Oshiro	Ki
	Duncan	Dwayne

The resulting array stored in :code:`$data` in the example above will
be:

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


