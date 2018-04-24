<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 *
 * @coversNothing
 */
class ViewTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $container;

    protected function _before()
    {
        $Helper          = $this->getModule('\Helper\Unit');
        $this->container = $Helper->getContainer();
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }

    protected function _after()
    {
    }

    // tests

    public function testAcinsertView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/acinsert.php';
        $this->assertTrue($controller->controller_name == 'AcinsertController');
    }

    public function testAggregatesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/aggregates.php';
        $this->assertTrue($controller->controller_name == 'AggregatesController');
    }

    public function testAlldbView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/alldb.php';
        $this->assertTrue($controller->controller_name == 'AlldbController');
    }

    public function testBrowserView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/browser.php';
        $this->assertTrue($controller->controller_name == 'BrowserController');
    }

    public function testCastsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/casts.php';
        $this->assertTrue($controller->controller_name == 'CastsController');
    }

    public function testColpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/colproperties.php';
        $this->assertTrue($controller->controller_name == 'ColpropertiesController');
    }

    public function testConstraintsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/constraints.php';
        $this->assertTrue($controller->controller_name == 'ConstraintsController');
    }

    public function testConversionsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/conversions.php';
        $this->assertTrue($controller->controller_name == 'ConversionsController');
    }

    public function testDatabaseView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/database.php';
        $this->assertTrue($controller->controller_name == 'DatabaseController');
    }

    public function testDataexportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/dataexport.php';
        $this->assertTrue($controller->controller_name == 'DataexportController');
    }

    public function testDataimportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/dataimport.php';
        $this->assertTrue($controller->controller_name == 'DataimportController');
    }

    public function testDbexportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/dbexport.php';
        $this->assertTrue($controller->controller_name == 'DbexportController');
    }

    public function testDisplayView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/display.php';
        $this->assertTrue($controller->controller_name == 'DisplayController');
    }

    public function testDomainsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/domains.php';
        $this->assertTrue($controller->controller_name == 'DomainsController');
    }

    public function testFulltextView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/fulltext.php';
        $this->assertTrue($controller->controller_name == 'FulltextController');
    }

    public function testFunctionsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/functions.php';
        $this->assertTrue($controller->controller_name == 'FunctionsController');
    }

    public function testGroupsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/groups.php';
        $this->assertTrue($controller->controller_name == 'GroupsController');
    }

    public function testHelpView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/help.php';
        $this->assertTrue($controller->controller_name == 'HelpController');
    }

    public function testHistoryView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/history.php';
        $this->assertTrue($controller->controller_name == 'HistoryController');
    }

    public function testIndexesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/indexes.php';
        $this->assertTrue($controller->controller_name == 'IndexesController');
    }

    public function testInfoView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/info.php';
        $this->assertTrue($controller->controller_name == 'InfoController');
    }

    public function testIntroView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/intro.php';
        $this->assertTrue($controller->controller_name == 'IntroController');
    }

    public function testLanguagesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/languages.php';
        $this->assertTrue($controller->controller_name == 'LanguagesController');
    }

    public function testLoginView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/login.php';
        $this->assertTrue($controller->controller_name == 'LoginController');
    }

    public function testMaterializedviewpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/materializedviewproperties.php';
        $this->assertTrue($controller->controller_name == 'MaterializedviewpropertiesController');
    }

    public function testMaterializedviewsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/materializedviews.php';
        $this->assertTrue($controller->controller_name == 'MaterializedviewsController');
    }

    public function testOpclassesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/opclasses.php';
        $this->assertTrue($controller->controller_name == 'OpclassesController');
    }

    public function testOperatorsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/operators.php';
        $this->assertTrue($controller->controller_name == 'OperatorsController');
    }

    public function testPrivilegesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/privileges.php';
        $this->assertTrue($controller->controller_name == 'PrivilegesController');
    }

    public function testRolesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/roles.php';
        $this->assertTrue($controller->controller_name == 'RolesController');
    }

    public function testRulesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/rules.php';
        $this->assertTrue($controller->controller_name == 'RulesController');
    }

    public function testSchemasView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/schemas.php';
        $this->assertTrue($controller->controller_name == 'SchemasController');
    }

    public function testSequencesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/sequences.php';
        $this->assertTrue($controller->controller_name == 'SequencesController');
    }

    public function testServersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/servers.php';
        $this->assertTrue($controller->controller_name == 'ServersController');
    }

    public function testSqleditView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/sqledit.php';
        $this->assertTrue($controller->controller_name == 'SqleditController');
    }

    public function testSqlView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/sql.php';
        $this->assertTrue($controller->controller_name == 'SqlController');
    }

    public function testTablespacesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/tablespaces.php';
        $this->assertTrue($controller->controller_name == 'TablespacesController');
    }

    public function testTablesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/tables.php';
        $this->assertTrue($controller->controller_name == 'TablesController');
    }

    public function testTblpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/tblproperties.php';
        $this->assertTrue($controller->controller_name == 'TblpropertiesController');
    }

    public function testTriggersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/triggers.php';
        $this->assertTrue($controller->controller_name == 'TriggersController');
    }

    public function testTypesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/types.php';
        $this->assertTrue($controller->controller_name == 'TypesController');
    }

    public function testUsersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/users.php';
        $this->assertTrue($controller->controller_name == 'UsersController');
    }

    public function testViewpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/viewproperties.php';
        $this->assertTrue($controller->controller_name == 'ViewpropertiesController');
    }

    public function testViewsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH.'/tests/views/views.php';
        $this->assertTrue($controller->controller_name == 'ViewsController');
    }
}
