<?php

/**
 * PHPPgAdmin 6.1.3
 */

/**
 * @internal
 * @coversNothing
 */
class UserEntitiesTest extends \Codeception\Test\Unit
{
    protected static $BASE_PATH;

    protected $_container;

    //const BASE_PATH = self::BASE_PATH;

    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests

    public function testGroupsView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/groups.php';
        $controller = groupsFactory($_container);
        self::assertSame($controller->controller_name, 'GroupsController', 'controller name should be GroupsController');
    }

    public function testPrivilegesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/privileges.php';
        $controller = privilegesFactory($_container);
        self::assertSame($controller->controller_name, 'PrivilegesController', 'controller name should be PrivilegesController');
    }

    public function testRolesView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/roles.php';
        $controller = rolesFactory($_container);
        self::assertSame($controller->controller_name, 'RolesController', 'controller name should be RolesController');
    }

    public function testUsersView(): void
    {
        $_container = $this->container;

        require self::$BASE_PATH . '/tests/views/users.php';
        $controller = usersFactory($_container);
        self::assertSame($controller->controller_name, 'UsersController', 'controller name should be UsersController');
    }

    protected function _before(): void
    {
        $Helper = $this->getModule('\Helper\Unit');
        $this->container = $Helper::getContainer();
        self::$BASE_PATH = self::$BASE_PATH = $this->container->BASE_PATH;
        $this->container->get('misc')->setNoDBConnection(true);
        // Helper
        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);
    }
}
