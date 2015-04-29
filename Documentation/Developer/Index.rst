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

	$services = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::findService('connector', 'csv');
	if ($services === FALSE) {
		// Issue an error
	} else {
		$connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('connector', 'csv');
	}

On the first line, you get a list of all services that are of type
“connector” and subtype “csv”. If the result if false, it means no
appropriate services were found and you probably want to issue an
error message.

On the contrary you are assured that there's at least one valid
service and you can get an instance of it by calling
:code:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService()` .

The next step is simply to call the appropriate method from the API –
with the right parameters – depending on which format you want to have
in return. For a PHP array:

.. code-block:: php

	$parameters = array(
		'filename' => 'path/to/your/file',
		'delimiter' => “\t”,
		'text_qualifier' => '',
		'encoding' => 'utf-8',
		'skip_rows' => 1,
	);
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
:code:`\TYPO3\CMS\Core\Utility\GeneralUtility::array2xml\_cs()`.


