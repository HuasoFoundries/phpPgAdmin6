<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class HelpController extends BaseController {
	public $_name = 'HelpController';

	function doDefault() {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		if (isset($_REQUEST['help'])) {
			$url = $data->getHelp($_REQUEST['help']);

			if (is_array($url)) {
				$this->doChoosePage($url);
				return;
			}

			if ($url) {
				header("Location: $url");
				exit;
			}
		}

		$this->doBrowse($lang['strinvalidhelppage']);
	}

	function doBrowse($msg = '') {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$misc->printHeader($lang['strhelppagebrowser']);
		$misc->printBody();

		$misc->printTitle($lang['strselecthelppage']);

		echo $misc->printMsg($msg);

		echo "<dl>\n";

		$pages = $data->getHelpPages();
		foreach ($pages as $page => $dummy) {
			echo "<dt>{$page}</dt>\n";

			$urls = $data->getHelp($page);
			if (!is_array($urls)) {
				$urls = [$urls];
			}

			foreach ($urls as $url) {
				echo "<dd><a href=\"{$url}\">{$url}</a></dd>\n";
			}
		}

		echo "</dl>\n";

		$misc->printFooter();
	}

	function doChoosePage($urls) {
		$conf = $this->conf;
		$misc = $this->misc;
		$lang = $this->lang;
		$data = $misc->getDatabaseAccessor();

		$misc->printHeader($lang['strhelppagebrowser']);
		$misc->printBody();

		$misc->printTitle($lang['strselecthelppage']);

		echo "<ul>\n";
		foreach ($urls as $url) {
			echo "<li><a href=\"{$url}\">{$url}</a></li>\n";
		}
		echo "</ul>\n";

		$misc->printFooter();
	}

}
