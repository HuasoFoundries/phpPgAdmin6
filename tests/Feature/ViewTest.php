<?php
namespace Tests\Feature;
beforeEach(function() {
    $this->container=containerInstance();
    $this->container->get('misc')->setNoDBConnection(true);

    
});
 

test('view snippet renders the right controller',function($viewName,$methodName,$controller_name) {
    $_container = $this->container;
    require dirname(__DIR__,2) . sprintf('/tests/views/%s.php',$viewName);
    @ob_start();
    $controller = $methodName($_container);
    $output = \ob_get_clean();

    expect($controller->controller_name)->toBe($controller_name);
})->with([
    ['acinsert','acinsertFactory','AcinsertController'],
    ['aggregates','aggregatesFactory','AggregatesController'],
    ['casts','castsFactory','CastsController'],
    ['conversions','conversionsFactory','ConversionsController'],
    ['domains','domainsFactory','DomainsController'],
    ['fulltext','fulltextFactory','FulltextController'],
    ['functions','functionsFactory','FunctionsController'],
    ['languages','languagesFactory','LanguagesController'],
    ['opclasses','opclassesFactory','OpclassesController'],
    ['operators','operatorsFactory','OperatorsController'],
    ['rules','rulesFactory','RulesController'],
    ['triggers','triggersFactory','TriggersController'],
    ['types','typesFactory','TypesController'],
    ['dataexport','dataexportFactory','DataexportController'],
['dataimport','dataimportFactory','DataimportController'],
['dbexport','dbexportFactory','DbexportController'],
['display','displayFactory','DisplayController'],
['alldb', 'alldbFactory',         'AlldbController'],
['browser', 'browserFactory',         'BrowserController'],
['database', 'databaseFactory',         'DatabaseController'],
['help', 'helpFactory',         'HelpController'],
['history', 'historyFactory',         'HistoryController'],
['info', 'infoFactory',         'InfoController'],
['intro', 'introFactory',         'IntroController'],
['login', 'loginFactory',         'LoginController'],
['schemas', 'schemasFactory',         'SchemasController'],
['servers', 'serversFactory',         'ServersController'],
['sql', 'sqlFactory',         'SqlController'],
['sqledit', 'sqleditFactory',         'SqleditController'],
['tablespaces','tablespacesFactory', 'TablespacesController'],
['materializedviewproperties','materializedviewpropertiesFactory', 'MaterializedviewpropertiesController'],
['materializedviews','materializedviewsFactory', 'MaterializedviewsController'],
['tables','tablesFactory', 'TablesController'],
['colproperties','colpropertiesFactory', 'ColpropertiesController'],
['constraints','constraintsFactory', 'ConstraintsController'],
['sequences','sequencesFactory', 'SequencesController'],
['indexes','indexesFactory', 'IndexesController'],
['tblproperties','tblpropertiesFactory', 'TblpropertiesController'],
['viewproperties','viewpropertiesFactory', 'ViewpropertiesController'],
['views','viewsFactory', 'ViewsController'],
['groups', 'groupsFactory','GroupsController'],
['privileges', 'privilegesFactory','PrivilegesController'],
['roles', 'rolesFactory','RolesController'],
['users', 'usersFactory','UsersController']
]);

