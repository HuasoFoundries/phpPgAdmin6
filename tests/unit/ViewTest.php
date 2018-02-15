<?php

/**
 * This test aims to check that every view {viewname}.php instances a class {Viewname}Controller
 * As a side effect, it checks if said class exists
 * Asserts that the instanced class has a $controller_name member property equal to the classname.
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
    }

    protected function _after()
    {
    }

    // tests

    public function testAcinsertView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/acinsert.php';
        $this->assertTrue($controller->controller_name == 'AcinsertController');
    }

    public function testAggregatesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/aggregates.php';
        $this->assertTrue($controller->controller_name == 'AggregatesController');
    }

    public function testAlldbView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/alldb.php';
        $this->assertTrue($controller->controller_name == 'AlldbController');
    }

    public function testBrowserView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/browser.php';
        $this->assertTrue($controller->controller_name == 'BrowserController');
    }

    public function testCastsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/casts.php';
        $this->assertTrue($controller->controller_name == 'CastsController');
    }

    public function testColpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/colproperties.php';
        $this->assertTrue($controller->controller_name == 'ColpropertiesController');
    }

    public function testConstraintsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/constraints.php';
        $this->assertTrue($controller->controller_name == 'ConstraintsController');
    }

    public function testConversionsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/conversions.php';
        $this->assertTrue($controller->controller_name == 'ConversionsController');
    }

    public function testDatabaseView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/database.php';
        $this->assertTrue($controller->controller_name == 'DatabaseController');
    }

    public function testDataexportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/dataexport.php';
        $this->assertTrue($controller->controller_name == 'DataexportController');
    }

    public function testDataimportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/dataimport.php';
        $this->assertTrue($controller->controller_name == 'DataimportController');
    }

    public function testDbexportView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/dbexport.php';
        $this->assertTrue($controller->controller_name == 'DbexportController');
    }

    public function testDisplayView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/display.php';
        $this->assertTrue($controller->controller_name == 'DisplayController');
    }

    public function testDomainsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/domains.php';
        $this->assertTrue($controller->controller_name == 'DomainsController');
    }

    public function testFulltextView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/fulltext.php';
        $this->assertTrue($controller->controller_name == 'FulltextController');
    }

    public function testFunctionsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/functions.php';
        $this->assertTrue($controller->controller_name == 'FunctionsController');
    }

    public function testGroupsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/groups.php';
        $this->assertTrue($controller->controller_name == 'GroupsController');
    }

    public function testHelpView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/help.php';
        $this->assertTrue($controller->controller_name == 'HelpController');
    }

    public function testHistoryView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/history.php';
        $this->assertTrue($controller->controller_name == 'HistoryController');
    }

    public function testIndexesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/indexes.php';
        $this->assertTrue($controller->controller_name == 'IndexesController');
    }

    public function testInfoView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/info.php';
        $this->assertTrue($controller->controller_name == 'InfoController');
    }

    public function testIntroView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/intro.php';
        $this->assertTrue($controller->controller_name == 'IntroController');
    }

    public function testLanguagesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/languages.php';
        $this->assertTrue($controller->controller_name == 'LanguagesController');
    }

    public function testLoginView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/login.php';
        $this->assertTrue($controller->controller_name == 'LoginController');
    }

    public function testMaterializedviewpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/materializedviewproperties.php';
        $this->assertTrue($controller->controller_name == 'MaterializedviewpropertiesController');
    }

    public function testMaterializedviewsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/materializedviews.php';
        $this->assertTrue($controller->controller_name == 'MaterializedviewsController');
    }

    public function testOpclassesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/opclasses.php';
        $this->assertTrue($controller->controller_name == 'OpclassesController');
    }

    public function testOperatorsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/operators.php';
        $this->assertTrue($controller->controller_name == 'OperatorsController');
    }

    public function testPrivilegesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/privileges.php';
        $this->assertTrue($controller->controller_name == 'PrivilegesController');
    }

    public function testRolesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/roles.php';
        $this->assertTrue($controller->controller_name == 'RolesController');
    }

    public function testRulesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/rules.php';
        $this->assertTrue($controller->controller_name == 'RulesController');
    }

    public function testSchemasView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/schemas.php';
        $this->assertTrue($controller->controller_name == 'SchemasController');
    }

    public function testSequencesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/sequences.php';
        $this->assertTrue($controller->controller_name == 'SequencesController');
    }

    public function testServersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/servers.php';
        $this->assertTrue($controller->controller_name == 'ServersController');
    }

    public function testSqleditView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/sqledit.php';
        $this->assertTrue($controller->controller_name == 'SqleditController');
    }

    public function testSqlView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/sql.php';
        $this->assertTrue($controller->controller_name == 'SqlController');
    }

    public function testTablespacesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/tablespaces.php';
        $this->assertTrue($controller->controller_name == 'TablespacesController');
    }

    public function testTablesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/tables.php';
        $this->assertTrue($controller->controller_name == 'TablesController');
    }

    public function testTblpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/tblproperties.php';
        $this->assertTrue($controller->controller_name == 'TblpropertiesController');
    }

    public function testTriggersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/triggers.php';
        $this->assertTrue($controller->controller_name == 'TriggersController');
    }

    public function testTypesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/types.php';
        $this->assertTrue($controller->controller_name == 'TypesController');
    }

    public function testUsersView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/users.php';
        $this->assertTrue($controller->controller_name == 'UsersController');
    }

    public function testViewpropertiesView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/viewproperties.php';
        $this->assertTrue($controller->controller_name == 'ViewpropertiesController');
    }

    public function testViewsView()
    {
        $container = $this->container;
        $container->misc->setNoDBConnection(true);
        require BASE_PATH . '/src/views/views.php';
        $this->assertTrue($controller->controller_name == 'ViewsController');
    }
}
