<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.5 support
 *
 */

class Postgres95 extends Postgres {

	var $major_version = 9.5;

	// Help functions

	function getHelpPages() {
		include_once BASE_PATH . '/src/help/PostgresDoc95.php';
		return $this->help_page;
	}

}
