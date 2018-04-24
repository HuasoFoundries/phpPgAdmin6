<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the group management.
 * It includes create/drop/alter/list groups.
 *
 * @coversNothing
 */
class GroupsTest extends PreconditionSet
{
    // Declare the member variable for group name.
    private $_groupName = 'testgroup';

    public function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login($SUPER_USER_NAME, $SUPER_USER_PASSWORD, "${webUrl}/login");

        return true;
    }

    public function tearDown()
    {
        $this->logout();

        return true;
    }

    /*
     * TestCaseID: SCG01
     * Test to create group.
     */
    public function testCreate()
    {
        global $webUrl;
        global $POWER_USER_NAME;
        global $NORMAL_USER_NAME;
        global $lang, $SERVER;

        // Turn to create group page.
        $this->assertTrue($this->get("${webUrl}/groups", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($lang['strcreategroup']));

        // Enter the information for creating group.
        $this->assertTrue($this->setField('name', $this->_groupName));
        $this->assertTrue($this->setField('members[]', [$POWER_USER_NAME, $NORMAL_USER_NAME]));

        // Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));
        $this->assertText($lang['strgroupcreated']);
        $this->assertText($this->_groupName);

        return true;
    }

    /*
     * TestCaseID: SAG01
     * Test to add users to the gruop.
     */
    public function testAddUser()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $POWER_USER_NAME;
        global $NORMAL_USER_NAME;
        global $lang, $SERVER;

        // Turn to the gruop's properties page.
        $this->assertTrue($this->get("${webUrl}/groups", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get(
                "${webUrl}/groups",
                ['action'     => 'properties',
                    'group'   => $this->_groupName,
                    'server'  => $SERVER]
            )
        );

        // Select user and add it to the group.
        $this->assertTrue($this->setField('user', $SUPER_USER_NAME));
        $this->assertTrue($this->clickSubmit($lang['straddmember']));
        $this->assertTrue($this->setField('user', $POWER_USER_NAME));
        $this->assertTrue($this->clickSubmit($lang['straddmember']));

        // Verify the group's memebers.
        $this->assertText($SUPER_USER_NAME);
        $this->assertText($POWER_USER_NAME);
        $this->assertText($NORMAL_USER_NAME);

        return true;
    }

    /*
     * TestCaseID: SRG01
     * Test to Remove users from the group.
     */
    public function testRemoveUser()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $POWER_USER_NAME;
        global $NORMAL_USER_NAME;
        global $lang, $SERVER;

        // Turn to the gruop properties page.
        $this->assertTrue($this->get("${webUrl}/groups", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get(
                "${webUrl}/groups",
                ['action'     => 'properties',
                    'group'   => $this->_groupName,
                    'server'  => $SERVER]
            )
        );

        // Drop users from the group and verify it.
        $this->assertTrue($this->clickLink($lang['strdrop']));
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertText($lang['strmemberdropped']);

        return true;
    }

    /*
     * TestCaseID: SDG01
     * Test to drop the group.
     */
    public function testDrop()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to the drop group page..
        $this->assertTrue($this->get("${webUrl}/groups", ['server' => $SERVER]));
        $this->assertTrue(
            $this->get(
                "${webUrl}/groups",
                ['server'     => $SERVER,
                    'action'  => 'confirm_drop',
                    'group'   => $this->_groupName]
            )
        );

        // Confirm to drop the group and verify it.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertText($lang['strgroupdropped']);
        $this->assertNoText($this->_groupName);

        return true;
    }
}
