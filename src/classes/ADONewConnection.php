<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

use ADODB_postgres9;

/**
 * Extends \ADODB_postgres9 to let correct inference on PHPDoc params.
 */
class ADONewConnection extends ADODB_postgres9
{
}
