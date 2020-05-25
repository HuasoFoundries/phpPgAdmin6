<?php

/**
 * PHPPgAdmin 6.0.0
 */

/**
 * PHPPgAdmin v6.0.0-RC9.
 *
 * @internal
 * @coversNothing
 */
class PublicSectionsTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    protected $_container;

    //const BASE_PATH = self::BASE_PATH;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testAlldbView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/alldb.php';
        $controller = alldbFactory($_container);
        self::assertSame($controller->controller_name, 'AlldbController', 'controller name should be AlldbController');
    }

    public function testBrowserView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/browser.php';
        $controller = browserFactory($_container);
        self::assertSame($controller->controller_name, 'BrowserController', 'controller name should be BrowserController');
    }

    public function testDatabaseView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/database.php';
        $controller = databaseFactory($_container);
        self::assertSame($controller->controller_name, 'DatabaseController', 'controller name should be DatabaseController');
    }

    public function testHelpView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/help.php';
        $controller = helpFactory($_container);
        self::assertSame($controller->controller_name, 'HelpController', 'controller name should be HelpController');
    }

    public function testHistoryView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/history.php';
        $controller = historyFactory($_container);
        self::assertSame($controller->controller_name, 'HistoryController', 'controller name should be HistoryController');
    }

    public function testInfoView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/info.php';
        $controller = infoFactory($_container);
        self::assertSame($controller->controller_name, 'InfoController', 'controller name should be InfoController');
    }

    public function testIntroView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/intro.php';
        $controller = introFactory($_container);
        self::assertSame($controller->controller_name, 'IntroController', 'controller name should be IntroController');
    }

    public function testLoginView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/login.php';
        $controller = loginFactory($_container);
        self::assertSame($controller->controller_name, 'LoginController', 'controller name should be LoginController');
    }

    public function testSchemasView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/schemas.php';
        $controller = schemasFactory($_container);
        self::assertSame($controller->controller_name, 'SchemasController', 'controller name should be SchemasController');
    }

    public function testServersView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/servers.php';
        $controller = serversFactory($_container);
        self::assertSame($controller->controller_name, 'ServersController', 'controller name should be ServersController');
    }

    public function testSqlView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/sql.php';
        $controller = sqlFactory($_container);
        self::assertSame($controller->controller_name, 'SqlController', 'controller name should be SqlController');
    }

    public function testSqleditView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/sqledit.php';
        $controller = sqleditFactory($_container);
        self::assertSame($controller->controller_name, 'SqleditController', 'controller name should be SqleditController');
    }

    protected function _before(): void
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();
        self::$BASE_PATH = $Helper::BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
        // Helper
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }
}
