<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.4 support
 *
 */

class Postgres94 extends Postgres {

	var $major_version = 9.4;

	// Help functions

	function getHelpPages() {
		include_once './help/PostgresDoc94.php';
		return $this->help_page;
	}

}
