<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.2 support
 *
 */

class Postgres92 extends Postgres93 {

	var $major_version = 9.2;

	function __construct(&$conn) {
		\PC::debug(['class' => __CLASS__, 'major_version' => $this->major_version], 'instanced connection class');
		$this->conn = $conn;
	}
	// Help functions

	function getHelpPages() {
		include_once BASE_PATH . '/help/PostgresDoc92.php';
		return $this->help_page;
	}

}
