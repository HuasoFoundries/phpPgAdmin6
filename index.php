<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions
$_no_db_connection = true;
include_once './libraries/lib.inc.php';
$misc->printHeader('', null, true);

$rtl = (strcasecmp($lang['applangdir'], 'rtl') == 0);

$cols = $rtl ? '*,' . $conf['left_width'] : $conf['left_width'] . ',*';
$mainframe = '<frame src="intro.php" name="detail" id="detail" frameborder="0" />';

echo '<frameset cols="' . $cols . '">';

if ($rtl) {
	echo $mainframe;
}

echo '<frame src="browser.php" name="browser" id="browser" frameborder="0" />';

if (!$rtl) {
	echo $mainframe;
}

echo '<noframes>';
echo '<body>';
echo $lang['strnoframes'];
echo '<br />';
echo '<a href="intro.php">' . $lang['strnoframeslink'] . '</a>';
echo '</body>';
echo '</noframes>';

echo '</frameset>';

$misc->printFooter(false);
