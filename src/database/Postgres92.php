<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.2 support
 *
 */

class Postgres92 extends Postgres93 {

	public $major_version = 9.2;

	// Help functions

	public function getHelpPages() {
		include_once BASE_PATH . '/src/help/PostgresDoc92.php';
		return $this->help_page;
	}

}
