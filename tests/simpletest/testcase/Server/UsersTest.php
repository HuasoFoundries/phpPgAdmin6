<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

// Import the precondition class.
require_once 'Public/SetPrecondition.php';

/**
 * This class is to test the user management.
 * It includes create/drop/alter/list users.
 *
 * @coversNothing
 */
class UsersTest extends PreconditionSet
{
    // Declare the user names which are created, altered and dropped in the class.
    private $_superUserName = 'superuser';
    private $_powerUserName = 'poweruser';

    public function setUp()
    {
        global $webUrl, $SUPER_USER_NAME, $SUPER_USER_PASSWORD, $SERVER;

        $login_url = "${webUrl}/login";

        $this->login($SUPER_USER_NAME, $SUPER_USER_PASSWORD, $login_url);

        return true;
    }

    public function tearDown()
    {
        $this->logout();

        return true;
    }

    /*
     * TestCaseID: SCU01
     * Test to create super user.
     */
    public function testCreateSuper()
    {
        global $webUrl;
        global $lang, $SERVER;

        //echo $this->getCookie('PHPCOVERAGE_HOME');

        // Turn to the "Create user" page.
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));

        $this->assertTrue($this->clickLink($lang['strcreateuser']));

        // Enter information for creating a user.
        $this->assertTrue($this->setField('formUsername', $this->_superUserName));
        $this->assertTrue($this->setField('formPassword', '123456'));
        $this->assertTrue($this->setField('formConfirm', '123456'));
        $this->assertTrue($this->setField('formSuper', true));
        $this->assertTrue($this->setField('formCreateDB', true));

        // Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));
        $this->assertText($this->_superUserName);

        return true;
    }

    /*
     * TestCaseID: SCU02
     * Test to create power user.
     */
    public function testCreatePower()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to the "Create user" page.
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($lang['strcreateuser']));

        // Enter information for creating a user.
        $this->assertTrue($this->setField('formUsername', $this->_powerUserName));
        $this->assertTrue($this->setField('formPassword', '123456'));
        $this->assertTrue($this->setField('formConfirm', '123456'));
        $this->assertTrue($this->setField('formCreateDB', true));

        //Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));
        $this->assertText($this->_powerUserName);

        return true;
    }

    /*
     * TestCaseID: SLU01
     * Test to list all the users.
     */
    public function testListUsers()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $POWER_USER_NAME;
        global $NORMAL_USER_NAME;
        global $lang, $SERVER;

        // Get the users list page and verify it.
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));
        $this->assertText($SUPER_USER_NAME);
        $this->assertText($POWER_USER_NAME);
        $this->assertText($NORMAL_USER_NAME);

        return true;
    }

    /*
     * TestCaseID: SAU01
     * Test to alter existing user's properties.
     */
    public function testAlter()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to the "alter user" page.
        $this->assertTrue($this->get("${webUrl}/users"));
        $this->assertTrue(
            $this->get("${webUrl}/users", [
                'action'   => 'edit',
                'username' => $this->_superUserName,
                'server'   => $SERVER, ])
        );

        // Enter the information for altering the user's properties.
        $this->assertTrue($this->setField('newname', $this->_superUserName));
        $this->assertTrue($this->setField('formPassword', '56789'));
        $this->assertTrue($this->setField('formConfirm', '56789'));
        $this->assertTrue($this->setField('formSuper', true));
        $this->assertTrue($this->setField('formCreateDB', false));

        // Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['stralter']));
        $this->assertText($this->_superUserName);

        return true;
    }

    /*
     * TestCaseID: SDU01
     * Test to drop existing user.
     */
    public function testDrop()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to the drop user page..
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get("${webUrl}/users", [
                'action'   => 'confirm_drop',
                'username' => $this->_superUserName,
                'server'   => $SERVER, ])
        );

        // Confirm to drop the user and verify it.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertNoText($this->_superUserName);

        return true;
    }

    /*
     * TestCaseID: SDU02
     * Test to drop existing user when the user is login.
     */
    public function testDropLogin()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Create a new browser to login the power user which we want to drop.
        $newBrowser = $this->createBrowser();
        $newBrowser->setCookie('IN_TEST', '1');
        $newBrowser->get("${webUrl}/login", ['server' => $SERVER]);
        $this->assertTrue($newBrowser->setField('loginUsername', $this->_powerUserName));
        $this->assertTrue($newBrowser->setFieldById('loginPassword', '123456'));
        $this->assertTrue($newBrowser->clickSubmit('Login'));
        $this->assertTrue($newBrowser->get("${webUrl}/alldb", ['server' => $SERVER]));

        // Turn to the old browser which we login with super user at very beginning.
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get("${webUrl}/users", ['action'     => 'confirm_drop',
                'username'                              => $this->_powerUserName,
                'server'                                => $SERVER, ])
        );

        // Confirm to drop the user and verify it.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertNoText($this->_powerUserName);

        // Go back to the power user browser and try to create the database.
        // It will log out and $lang['strloginfailed'] will be displayed in the page.
        $this->setBrowser($newBrowser);

        $this->assertTrue($this->clickLink($lang['strcreatedatabase']));
        $this->showText();
        $this->assertText($lang['strloginfailed']);

        return true;
    }

    /*
     * TestCaseID: SDU03
     * Test to drop the user self.
     */
    public function testDropSelf()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $lang, $SERVER;

        // Turn to the drop user page..
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get("${webUrl}/users", [
                'action'   => 'confirm_drop',
                'username' => $SUPER_USER_NAME,
                'server'   => $SERVER, ])
        );

        // Confirm to drop the user and verify it.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertText($SUPER_USER_NAME);
        $this->assertText($lang['struserdroppedbad']);

        return true;
    }
}
