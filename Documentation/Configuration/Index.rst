.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: /Includes.rst.txt


.. _configuration:

Configuration
-------------

This chapter describes the parameters that can be used to configure the CSV connector service.


.. _configuration-filename:

filename
^^^^^^^^

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


.. _configuration-method:

method
^^^^^^

Type
  string

Description
  Method used to get the file (GET, POST, or whatever else is relevant).
  This parameter is optional and the method defaults to GET.


.. _configuration-headers:

headers
^^^^^^^

Type
  array

Description
  Key-value pairs of headers that should be sent along with the request.

Example
  Example headers for setting an alternate user agent and defining what reponse
  format to accept.

  .. code-block:: php

      'headers' => [
         'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:75.0) Gecko/20100101 Firefox/75.0',
         'Accept' => 'text/csv',
      ]


.. _configuration-encoding:

encoding
^^^^^^^^

Type
  string

Description
  Encoding of the data found in the file. This value must match any of
  the encoding values recognized by the PHP libray "mbstring". See
  https://www.php.net/manual/en/mbstring.supported-encodings.php


.. _configuration-delimiter:

delimiter
^^^^^^^^^

Type
  string

Description
  The character used to separate the various fields on each line of the file.

Default
  , (comma)


.. _configuration-text-qualifier:

text\_qualifier
^^^^^^^^^^^^^^^

Type
  string

Description
  The character used to wrap text fields.

Default
  " (double quote)


.. _configuration-skip-rows:

skip\_rows
^^^^^^^^^^

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


.. _configuration-locale:

locale
^^^^^^

Type
  int

Description
  A locale string, according to the locales available on the server.

  This may not be necessary. However it may happen to be needed to
  handle badly-encoded files for example. The symptom is special
  characters – like umlauts – disappearing in the imported data.
