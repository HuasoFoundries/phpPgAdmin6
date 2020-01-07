<?php

/**
 * PHPPgAdmin v6.0.0-RC2
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
        $this->container->misc->setNoDBConnection(true);
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }

    protected function _after()
    {
    }

    // tests

    public function testAcinsertView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/acinsert.php';
        $this->assertSame($controller->controller_name, 'AcinsertController', 'controller name should be AcinsertController');
    }

    public function testAggregatesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/aggregates.php';
        $this->assertSame($controller->controller_name, 'AggregatesController', 'controller name should be AggregatesController');
    }

    public function testAlldbView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/alldb.php';
        $this->assertSame($controller->controller_name, 'AlldbController', 'controller name should be AlldbController');
    }

    public function testBrowserView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/browser.php';
        $this->assertSame($controller->controller_name, 'BrowserController', 'controller name should be BrowserController');
    }

    public function testCastsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/casts.php';
        $this->assertSame($controller->controller_name, 'CastsController', 'controller name should be CastsController');
    }

    public function testColpropertiesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/colproperties.php';
        $this->assertSame($controller->controller_name, 'ColpropertiesController', 'controller name should be ColpropertiesController');
    }

    public function testConstraintsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/constraints.php';
        $this->assertSame($controller->controller_name, 'ConstraintsController', 'controller name should be ConstraintsController');
    }

    public function testConversionsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/conversions.php';
        $this->assertSame($controller->controller_name, 'ConversionsController', 'controller name should be ConversionsController');
    }

    public function testDatabaseView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/database.php';
        $this->assertSame($controller->controller_name, 'DatabaseController', 'controller name should be DatabaseController');
    }

    public function testDataexportView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/dataexport.php';
        $this->assertSame($controller->controller_name, 'DataexportController', 'controller name should be DataexportController');
    }

    public function testDataimportView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/dataimport.php';
        $this->assertSame($controller->controller_name, 'DataimportController', 'controller name should be DataimportController');
    }

    public function testDbexportView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/dbexport.php';
        $this->assertSame($controller->controller_name, 'DbexportController', 'controller name should be DbexportController');
    }

    public function testDisplayView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/display.php';
        $this->assertSame($controller->controller_name, 'DisplayController', 'controller name should be DisplayController');
    }

    public function testDomainsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/domains.php';
        $this->assertSame($controller->controller_name, 'DomainsController', 'controller name should be DomainsController');
    }

    public function testFulltextView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/fulltext.php';
        $this->assertSame($controller->controller_name, 'FulltextController', 'controller name should be FulltextController');
    }

    public function testFunctionsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/functions.php';
        $this->assertSame($controller->controller_name, 'FunctionsController', 'controller name should be FunctionsController');
    }

    public function testGroupsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/groups.php';
        $this->assertSame($controller->controller_name, 'GroupsController', 'controller name should be GroupsController');
    }

    public function testHelpView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/help.php';
        $this->assertSame($controller->controller_name, 'HelpController', 'controller name should be HelpController');
    }

    public function testHistoryView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/history.php';
        $this->assertSame($controller->controller_name, 'HistoryController', 'controller name should be HistoryController');
    }

    public function testIndexesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/indexes.php';
        $this->assertSame($controller->controller_name, 'IndexesController', 'controller name should be IndexesController');
    }

    public function testInfoView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/info.php';
        $this->assertSame($controller->controller_name, 'InfoController', 'controller name should be InfoController');
    }

    public function testIntroView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/intro.php';
        $this->assertSame($controller->controller_name, 'IntroController', 'controller name should be IntroController');
    }

    public function testLanguagesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/languages.php';
        $this->assertSame($controller->controller_name, 'LanguagesController', 'controller name should be LanguagesController');
    }

    public function testLoginView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/login.php';
        $this->assertSame($controller->controller_name, 'LoginController', 'controller name should be LoginController');
    }

    public function testMaterializedviewpropertiesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/materializedviewproperties.php';
        $this->assertSame($controller->controller_name, 'MaterializedviewpropertiesController', 'controller name should be MaterializedviewpropertiesController');
    }

    public function testMaterializedviewsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/materializedviews.php';
        $this->assertSame($controller->controller_name, 'MaterializedviewsController', 'controller name should be MaterializedviewsController');
    }

    public function testOpclassesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/opclasses.php';
        $this->assertSame($controller->controller_name, 'OpclassesController', 'controller name should be OpclassesController');
    }

    public function testOperatorsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/operators.php';
        $this->assertSame($controller->controller_name, 'OperatorsController', 'controller name should be OperatorsController');
    }

    public function testPrivilegesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/privileges.php';
        $this->assertSame($controller->controller_name, 'PrivilegesController', 'controller name should be PrivilegesController');
    }

    public function testRolesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/roles.php';
        $this->assertSame($controller->controller_name, 'RolesController', 'controller name should be RolesController');
    }

    public function testRulesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/rules.php';
        $this->assertSame($controller->controller_name, 'RulesController', 'controller name should be RulesController');
    }

    public function testSchemasView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/schemas.php';
        $this->assertSame($controller->controller_name, 'SchemasController', 'controller name should be SchemasController');
    }

    public function testSequencesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/sequences.php';
        $this->assertSame($controller->controller_name, 'SequencesController', 'controller name should be SequencesController');
    }

    public function testServersView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/servers.php';
        $this->assertSame($controller->controller_name, 'ServersController', 'controller name should be ServersController');
    }

    public function testSqleditView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/sqledit.php';
        $this->assertSame($controller->controller_name, 'SqleditController', 'controller name should be SqleditController');
    }

    public function testSqlView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/sql.php';
        $this->assertSame($controller->controller_name, 'SqlController', 'controller name should be SqlController');
    }

    public function testTablespacesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/tablespaces.php';
        $this->assertSame($controller->controller_name, 'TablespacesController', 'controller name should be TablespacesController');
    }

    public function testTablesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/tables.php';
        $this->assertSame($controller->controller_name, 'TablesController', 'controller name should be TablesController');
    }

    public function testTblpropertiesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/tblproperties.php';
        $this->assertSame($controller->controller_name, 'TblpropertiesController', 'controller name should be TblpropertiesController');
    }

    public function testTriggersView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/triggers.php';
        $this->assertSame($controller->controller_name, 'TriggersController', 'controller name should be TriggersController');
    }

    public function testTypesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/types.php';
        $this->assertSame($controller->controller_name, 'TypesController', 'controller name should be TypesController');
    }

    public function testUsersView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/users.php';
        $this->assertSame($controller->controller_name, 'UsersController', 'controller name should be UsersController');
    }

    public function testViewpropertiesView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/viewproperties.php';
        $this->assertSame($controller->controller_name, 'ViewpropertiesController', 'controller name should be ViewpropertiesController');
    }

    public function testViewsView()
    {
        $container = $this->container;
        require BASE_PATH.'/tests/views/views.php';
        $this->assertSame($controller->controller_name, 'ViewsController', 'controller name should be ViewsController');
    }
}
