<?php
namespace PHPPgAdmin\Database;
/**
 * PostgreSQL 9.0 support
 *
 * $Id: Postgres82.php,v 1.10 2007/12/28 16:21:25 ioguix Exp $
 */

class Postgres90 extends Postgres91 {

	var $major_version = 9.0;

	function __construct(&$conn) {
		\PC::debug(['class' => __CLASS__, 'major_version' => $this->major_version], 'instanced connection class');
		$this->conn = $conn;
	}
	// Help functions

	function getHelpPages() {
		include_once BASE_PATH . '/help/PostgresDoc90.php';
		return $this->help_page;
	}

	// Capabilities

}
