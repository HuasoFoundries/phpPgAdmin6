<?php

/**
 * Available Themes for phpPgAdmin
 *
 * $Id:
 */

// List of themes

$appThemes = [
	'default' => 'Default',
	'cappuccino' => 'Cappuccino',
	'gotar' => 'Blue/Green',
	'bootstrap' => 'Bootstrap3',
];

/* select the theme */
unset($_theme);
if (!isset($conf['theme'])) {
	$conf['theme'] = 'default';
}
DEFINE('THEME_PATH', BASE_PATH . "/includes/themes");

// 1. Check for the theme from a request var
if (isset($_REQUEST['theme']) && is_file(THEME_PATH . "/{$_REQUEST['theme']}/global.css")) {
	/* save the selected theme in cookie for a year */
	setcookie('ppaTheme', $_REQUEST['theme'], time() + 31536000);
	$_theme = $_SESSION['ppaTheme'] = $conf['theme'] = $_REQUEST['theme'];
}

// 2. Check for theme session var
if (!isset($_theme) && isset($_SESSION['ppaTheme']) && is_file(THEME_PATH . "/{$_SESSION['ppaTheme']}/global.css")) {
	$conf['theme'] = $_SESSION['ppaTheme'];
}

// 3. Check for theme in cookie var
if (!isset($_theme) && isset($_COOKIE['ppaTheme']) && is_file(THEME_PATH . "/{$_COOKIE['ppaTheme']}/global.css")) {
	$conf['theme'] = $_COOKIE['ppaTheme'];
}

if (!is_null($_server_info)) {

	$_theme = '';

	if (isset($_server_info['theme']['default']) && is_file(THEME_PATH . "/{$_server_info['theme']['default']}/global.css")) {
		$_theme = $_server_info['theme']['default'];
	}

	if (isset($_REQUEST['database'])
		and isset($_server_info['theme']['db'][$_REQUEST['database']])
		and is_file(THEME_PATH . "/{$_server_info['theme']['db'][$_REQUEST['database']]}/global.css")
	) {
		$_theme = $_server_info['theme']['db'][$_REQUEST['database']];
	}

	if (isset($_server_info['username'])
		and isset($_server_info['theme']['user'][$_server_info['username']])
		and is_file(THEME_PATH . "/{$_server_info['theme']['user'][$_server_info['username']]}/global.css")
	) {
		$_theme = $_server_info['theme']['user'][$_server_info['username']];
	}

	if ($_theme !== '') {
		setcookie('ppaTheme', $_theme, time() + 31536000);
		$conf['theme'] = $_theme;
	}
}
