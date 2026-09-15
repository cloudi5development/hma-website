<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A bulk-upload file the importer cannot use at all — not a spreadsheet, no
 * rows, headings missing, too big.
 *
 * The message is written for the admin and is shown to them as it is. Problems
 * with individual ROWS are not exceptions; they are listed in the preview,
 * against the row, so the admin can fix all of them in one pass.
 */
class FormImportException extends RuntimeException
{
}
