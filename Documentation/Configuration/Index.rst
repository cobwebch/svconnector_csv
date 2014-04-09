.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
-------------

The various "fetch" methods of the CSV connector take the same
parameters:

+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| Parameter       | Data type     | Description                                                           | Default          |
+=================+===============+=======================================================================+==================+
| filename        | string        | This is the name of the file to read. It can be any file in the paths |                  |
|                 |               | accepted by TYPO3. It can use the "EXT:..." syntax.                   |                  |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| encoding        | string        | Encoding of the data found in the file. This value must match any of  |                  |
|                 |               | the encoding values or their synonyms found in class                  |                  |
|                 |               | :code:`\TYPO3\CMS\Core\Charset\CharsetConverter`.                     |                  |
|                 |               | Note that this means pretty much all the usual encodings.             |                  |
|                 |               | If unsure look at array                                               |                  |
|                 |               | :code:`\TYPO3\CMS\Core\Charset\CharsetConverter::synonyms`.           |                  |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| delimiter       | string        | The character used to separate the various fields on each line of the | , (comma)        |
|                 |               | file.                                                                 |                  |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| text\_qualifier | string        | The character used to wrap text fields.                               | " (double quote) |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| skip\_rows      | string        | The number of rows to ignore at the beginning of the file.            | 0                |
|                 |               |                                                                       |                  |
|                 |               | This has an additional special meaning if larger than 0. The CSV      |                  |
|                 |               | connector will take the first line and read column labels from it.    |                  |
|                 |               | These labels will then be used as keys in the array of data returned. |                  |
|                 |               |                                                                       |                  |
|                 |               | .. note::                                                             |                  |
|                 |               |                                                                       |                  |
|                 |               |    This does not apply when using the :code:`fetchRaw()` method.      |                  |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+
| locale          | string        | A locale string, according to the locales available on the server.    |                  |
|                 |               |                                                                       |                  |
|                 |               | This may not be necessary. However it may happen to be needed to      |                  |
|                 |               | handle badly-encoded files for example. The symptom is special        |                  |
|                 |               | characters – like umlauts – disappearing in the imported data.        |                  |
+-----------------+---------------+-----------------------------------------------------------------------+------------------+

The data is read using the PHP function :code:`filegetcsv()` which
takes care of the line endings. It also receives the delimiter and
text qualifier as parameters. Once the data is read it is converted
from the encoding given as a parameter to the current encoding (either
FE or BE, as stored in :code:`\TYPO3\CMS\Lang\LanguageService::$charSet`), if they are not the
same. If the encoding parameter is left empty, no conversion takes
place.
