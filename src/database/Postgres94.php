<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.4 support
 *
 */

class Postgres94 extends Postgres {

	var $major_version = 9.4;
	function __construct(&$conn) {
		\PC::debug(['class' => __CLASS__, 'major_version' => $this->major_version], 'instanced connection class');
		$this->conn = $conn;
	}
	// Help functions

	function getHelpPages() {
		include_once BASE_PATH . '/help/PostgresDoc94.php';
		return $this->help_page;
	}

}
