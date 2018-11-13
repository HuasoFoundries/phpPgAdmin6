<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the security management.
 * It includes login/logout and modify password.
 *
 * @coversNothing
 */
class SecurityTest extends PreconditionSet
{
    // Declare the member variables for the invalid username/password.
    private $_invalidUserName = 'invalidusername';
    private $_invalidPassword = 'invalidpassword';

    public function setUp()
    {
        return true;
    }

    public function tearDown()
    {
        return true;
    }

    /*
     * TestCaseID: CSM01
     * Test to login with special user name.
     */
    public function testSpecialLogin()
    {
        global $webUrl;
        global $NORMAL_USER_NAME;
        global $lang;

        // Login with special user name "postgres".
        $this->login('postgres', $this->_invalidPassword, "${webUrl}/login");

        // Verify the error messages.
        $this->assertText($lang['strlogindisallowed']);
        $this->assertText($lang['strviewfaq']);
        // Login with special user name "postgres".
        $this->login($NORMAL_USER_NAME, '', "${webUrl}/login");

        // Verify the error messages.
        $this->assertText($lang['strlogindisallowed']);
        $this->assertText($lang['strviewfaq']);

        return true;
    }

    /*
     * TestCaseID: CSM02
     * Test to login with invalid user name or password.
     */
    public function testInvalidLogin()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $lang;

        // Login with invalid user name.
        $this->login($this->_invalidUserName, $this->_invalidPassword, "${webUrl}/login");

        // Verify the error messages.
        $this->assertText($lang['strloginfailed']);

        // Login with valid username and invalid password.
        $this->login($SUPER_USER_NAME, $this->_invalidPassword, "${webUrl}/login");

        // Verify the error messages.
        $this->assertText($lang['strloginfailed']);

        return true;
    }

    /*
     * TestCaseID: CSM03
     * Test to change the current user's password.
     */
    public function testAccount()
    {
        global $webUrl;
        global $NORMAL_USER_NAME;
        global $NORMAL_USER_PASSWORD;
        global $lang, $SERVER;
        $newpassword = 'newpassword';

        $this->login($NORMAL_USER_NAME, $NORMAL_USER_PASSWORD, "${webUrl}/login");

        // Turn to the account page and change the password page.
        $this->assertTrue($this->get("${webUrl}/users", ['server' => $SERVER, 'action' => 'account']));
        $this->assertTrue($this->clickLink($lang['strchangepassword']));

        // Enter the new password and different confirm password.
        $this->assertTrue($this->setField('password', $newpassword));
        $this->assertTrue($this->setField('confirm', $this->_invalidPassword));

        // Then submit and verify the error messages.
        $this->assertTrue($this->clickSubmit($lang['strok']));
        $this->assertText($lang['strpasswordconfirm']);

        // Enter the new password and confirm password.
        $this->assertTrue($this->setField('password', $NORMAL_USER_PASSWORD));
        $this->assertTrue($this->setField('confirm', $NORMAL_USER_PASSWORD));

        // Then submit and verify the messages.
        $this->assertTrue($this->clickSubmit($lang['strok']));
        $this->assertText($lang['strpasswordchanged']);

        $this->logout();

        return true;
    }
}
