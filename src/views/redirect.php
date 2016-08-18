<?php
$subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : 'root';

if ($subject == 'root') {
	$_no_db_connection = true;
}

require_once '../lib.inc.php';

$url = $misc->getLastTabURL($subject);

$include_file = $url['url'];

// Load query vars into superglobal arrays
if (isset($url['urlvars'])) {
	$urlvars = [];

	foreach ($url['urlvars'] as $k => $urlvar) {
		$urlvars[$k] = value($urlvar, $_REQUEST);
	}

	$_REQUEST = array_merge($_REQUEST, $urlvars);
	$_GET     = array_merge($_GET, $urlvars);
}

echo '<pre>';
print_r($url['urlvars']);
echo '</pre>';
//PC::debug($url['url'], 'url');
//
include $include_file;
