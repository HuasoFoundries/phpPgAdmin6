<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.4 support
 *
 */

class Postgres94 extends Postgres {

	public $major_version = 9.4;

	// Help functions

	public function getHelpPages() {
		include_once BASE_PATH . '/src/help/PostgresDoc94.php';
		return $this->help_page;
	}

}
