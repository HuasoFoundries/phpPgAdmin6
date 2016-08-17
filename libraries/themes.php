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
DEFINE('THEME_PATH', BASE_PATH . "/libraries/themes");

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

if (!is_null($info)) {
	$_theme = '';

	if ((isset($info['theme']['default']))
		and is_file(THEME_PATH . "/{$info['theme']['default']}/global.css")
	) {
		$_theme = $info['theme']['default'];
	}

	if (isset($_REQUEST['database'])
		and isset($info['theme']['db'][$_REQUEST['database']])
		and is_file(THEME_PATH . "/{$info['theme']['db'][$_REQUEST['database']]}/global.css")
	) {
		$_theme = $info['theme']['db'][$_REQUEST['database']];
	}

	if (isset($info['username'])
		and isset($info['theme']['user'][$info['username']])
		and is_file(THEME_PATH . "/{$info['theme']['user'][$info['username']]}/global.css")
	) {
		$_theme = $info['theme']['user'][$info['username']];
	}

	if ($_theme !== '') {
		setcookie('ppaTheme', $_theme, time() + 31536000);
		$conf['theme'] = $_theme;
	}
}
