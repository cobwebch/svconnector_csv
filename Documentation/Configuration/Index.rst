.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: /Includes.rst.txt


.. _configuration:

Configuration
-------------

The various "fetch" methods of the CSV connector take the same parameters,
described below.


.. _configuration-parameters:

Connector parameters
^^^^^^^^^^^^^^^^^^^^


.. _configuration-parameters-filename:

filename
""""""""

Type
  string

Description
  This is the name of the file to read. The reference to the file can
  use any of the following syntaxes:

  - absolute file path: :file:`/var/foo/web/fileadmin/import/bar.csv`
    (within the TYPO3 root path or :code:`TYPO3_CONF_VARS[BE][lockRootPath]`)
  - file path relative to the TYPO3 root: :file:`fileadmin/import/foo.csv`
  - file path using :code:`EXT:`: :file:`EXT:foo/Resources/Private/Data/bar.csv`
  - fully qualified URL, e.g. :file:`http://www.example.com/foo.csv`
  - FAL reference with storage ID and file identifier: :file:`FAL:2:/foo.csv`
  - custom syntax: :file:`MYKEY:whatever_you_want`, see
    :ref:`Connector Services <svconnector:developers-utilities-reading-files>`


.. _configuration-parameters-encoding:

encoding
""""""""

Type
  string

Description
  Encoding of the data found in the file. This value must match any of
  the encoding values recognized by the PHP libray "mbstring". See
  https://www.php.net/manual/en/mbstring.supported-encodings.php


.. _configuration-parameters-delimiter:

delimiter
"""""""""

Type
  string

Description
  The character used to separate the various fields on each line of the file.

Default
  , (comma)


.. _configuration-parameters-text-qualifier:

text\_qualifier
"""""""""""""""

Type
  string

Description
  The character used to wrap text fields.

Default
  " (double quote)


.. _configuration-parameters-skip-rows:

skip\_rows
""""""""""

Type
  int

Description
  The number of rows to ignore at the beginning of the file.

  This has an additional special meaning if larger than 0. The CSV
  connector will take the first line and read column labels from it.
  These labels will then be used as keys in the array of data returned.
  The other skipped lines are totally ignored.

  .. note::

     This does not apply when using the :code:`fetchRaw()` method.

Default
  0


.. _configuration-parameters-locale:

locale
""""""

Type
  int

Description
  A locale string, according to the locales available on the server.

  This may not be necessary. However it may happen to be needed to
  handle badly-encoded files for example. The symptom is special
  characters – like umlauts – disappearing in the imported data.


.. _configuration-considerations:

Other considerations
^^^^^^^^^^^^^^^^^^^^

Once the data is read, it is converted from the encoding given as a parameter to the current encoding (either
FE or BE, as stored in :code:`\TYPO3\CMS\Lang\LanguageService::$charSet`), if they are not the
same. If the encoding parameter is left empty, no conversion takes place.

.. warning::

   Beware of incomplete data in the CSV file. It will result in variable results depending
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
