<?php

/**
 * Manage fulltext configurations, dictionaries and mappings
 *
 * $Id: fulltext.php,v 1.6 2008/03/17 21:35:48 ioguix Exp $
 */

// Include application functions
require_once '../lib.inc.php';

$fulltext_controller = new \PHPPgAdmin\Controller\FulltextController($container);

$misc->printHeader($lang['strschemas']);
$misc->printBody();

if (isset($_POST['cancel'])) {
	if (isset($_POST['prev_action'])) {
		$action = $_POST['prev_action'];
	} else {
		$action = '';
	}
}

switch ($action) {
	case 'createconfig':
		if (isset($_POST['create'])) {
			$fulltext_controller->doSaveCreateConfig();
		} else {
			$fulltext_controller->doCreateConfig();
		}

		break;
	case 'alterconfig':
		if (isset($_POST['alter'])) {
			$fulltext_controller->doSaveAlterConfig();
		} else {
			$fulltext_controller->doAlterConfig();
		}

		break;
	case 'dropconfig':
		if (isset($_POST['drop'])) {
			$fulltext_controller->doDropConfig(false);
		} else {
			$fulltext_controller->doDropConfig(true);
		}

		break;
	case 'viewconfig':
		$fulltext_controller->doViewConfig($_REQUEST['ftscfg']);
		break;
	case 'viewparsers':
		$fulltext_controller->doViewParsers();
		break;
	case 'viewdicts':
		$fulltext_controller->doViewDicts();
		break;
	case 'createdict':
		if (isset($_POST['create'])) {
			$fulltext_controller->doSaveCreateDict();
		} else {
			doCreateDict();
		}

		break;
	case 'alterdict':
		if (isset($_POST['alter'])) {
			$fulltext_controller->doSaveAlterDict();
		} else {
			$fulltext_controller->doAlterDict();
		}

		break;
	case 'dropdict':
		if (isset($_POST['drop'])) {
			$fulltext_controller->doDropDict(false);
		} else {
			$fulltext_controller->doDropDict(true);
		}

		break;
	case 'dropmapping':
		if (isset($_POST['drop'])) {
			$fulltext_controller->doDropMapping(false);
		} else {
			$fulltext_controller->doDropMapping(true);
		}

		break;
	case 'altermapping':
		if (isset($_POST['alter'])) {
			$fulltext_controller->doSaveAlterMapping();
		} else {
			$fulltext_controller->doAlterMapping();
		}

		break;
	case 'addmapping':
		if (isset($_POST['add'])) {
			$fulltext_controller->doSaveAddMapping();
		} else {
			$fulltext_controller->doAddMapping();
		}

		break;

	default:
		$fulltext_controller->doDefault();
		break;
}

$misc->printFooter();
