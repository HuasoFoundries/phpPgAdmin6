<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.3 support
 *
 */

class Postgres93 extends Postgres {

	var $major_version = 9.3;
	function __construct(&$conn) {
		\PC::debug(['class' => __CLASS__, 'major_version' => $this->major_version], 'instanced connection class');
		$this->conn = $conn;
	}
	// Help functions

	function getHelpPages() {
		include_once BASE_PATH . '/help/PostgresDoc93.php';
		return $this->help_page;
	}

}
