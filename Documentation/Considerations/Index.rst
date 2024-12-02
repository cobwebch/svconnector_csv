.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: /Includes.rst.txt


.. _special-considerations:

Special considerations
----------------------

.. _special-considerations-encoding:

Encoding
^^^^^^^^

Once the data is read, it is converted from the encoding given as a parameter to the current encoding (either
FE or BE, as stored in :code:`\TYPO3\CMS\Lang\LanguageService::$charSet`), if they are not the
same. If the encoding parameter is left empty, no conversion takes place.

.. _special-considerations-data-structure:

CSV data structure
^^^^^^^^^^^^^^^^^^

Beware of incomplete data in the CSV file. It will generate variable results depending
on the position of the missing data. Consider the following CSV data:

.. code-block:: text

   foo;12;aaa
   bar;42
   baz;;bbb
    ;;ccc
   36

Empty columns before the last one will be returned as empty strings. Missing columns at the end of each row
simply do not exist. The above data will be returned as the following PHP array:

.. code-block:: php

   'result' => [
      [
         'foo',
         '12',
         'aaa'
      ],
      // Missing columns at the end are totally missing in the result
      [
         'bar',
         '42'
      ],
      // Missing columns before the last one are returned as empty strings...
      [
         'baz',
         '',
         'bbb'
      ],
      // ...but spaces are preserved
      [
         ' ',
         '',
         'ccc'
      ],
      [
         '36'
      ]
   )
