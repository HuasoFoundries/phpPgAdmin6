<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

/**
 * Provides some base function here.
 *
 * Function list now:  login
 *                     createDatabase
 *                     dropDatabase(can not be run correctly)
 *                     createTable
 *                     dropTable
 *                     logout
 *
 * @coversNothing
 */
class PreconditionSet extends WebTestCase
{
    /**
     * Logoin phpPgAdmin use the specify user name, password
     * and login home page.
     *
     * The user name and password can be replaced.
     * But the param $loginPageUrl always equals
     * http://localhost:8080/phpPgAdmin/index.php
     *
     * @param string $userName     user name of the admin
     * @param string $password     password of the admin
     * @param string $loginPageUrl the login home page url
     */
    public function login($userName, $password, $loginPageUrl)
    {
        global $lang, $SERVER, $PHP_SIMPLETEST_HOME;

        $this->setCookie('PHPCOVERAGE_HOME', $PHP_SIMPLETEST_HOME);
        $this->setCookie('IN_TEST', '1');
        $this->get($loginPageUrl, ['server' => $SERVER]);

        $this->setField('loginUsername', $userName);
        $this->setFieldById('loginPassword', $password);
        $this->submitFormByid('login_form');

        return true;
    }

    /**
     * Create a new database by the giving database name and encoding.
     *
     * @param string $databaseName the name of the new database
     * @param string $enCoding     the encoding mode
     */
    public function createDatabase($databaseName, $enCoding)
    {
        global $webUrl;
        global $lang, $SERVER;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login(
            $SUPER_USER_NAME,

            $SUPER_USER_PASSWORD,
            "${webUrl}/login"
        );
        $this->get("${webUrl}/alldb", ['server' => $SERVER]);
        $this->clickLink('Create database');

        $this->setField('formName', $databaseName);
        $this->setField('formEncoding', $enCoding);

        // Click the button "Create" for creating a database
        // use the specifid name.
        $this->clickSubmit($lang['strcreate']);

        return true;
    }

    /**
     * Drop a exist database by the specify database name.
     *
     * @param string $databaseName the specify database name which be droped
     */
    public function dropDatabase($databaseName)
    {
        global $webUrl;
        global $lang, $SERVER;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login(
            $SUPER_USER_NAME,

            $SUPER_USER_PASSWORD,
            "${webUrl}/login"
        );

        $this->get("${webUrl}/alldb", ['server' => $SERVER]);
        $this->get(
            "${webUrl}/alldb",
            [
                'server'       => $SERVER,
                'action'       => 'confirm_drop',
                'subject'      => 'database',
                'database'     => $databaseName,
                'dropdatabase' => $databaseName, ]
        );
        $this->clickSubmit($lang['strdrop']);

        return true;
    }

    /**
     * Create a new table by the specified conditions.
     *
     * Notice:
     * If the value of $fieldNumber equals 3, it would be create a
     * table with 3 field like(field name, field type):(field0, text),
     * (field1, text), (field2, text).
     *
     * @param string $databaseName Owner of the new table. paramname.
     * @param string $schema       the Schema which the new table belong to
     * @param string $tablename    the name of the new table
     * @param string $fieldNumber  the field number of the new table
     * @param mixed  $tableName
     */
    public function createTable($databaseName, $schema, $tableName, $fieldNumber)
    {
        global $webUrl;
        global $lang, $SERVER;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login(
            $SUPER_USER_NAME,

            $SUPER_USER_PASSWORD,
            "${webUrl}/login"
        );

        $this->get(
            "${webUrl}/tables",

            [
                'server'   => $SERVER,
                'action'   => 'create',
                'database' => $databaseName,
                'schema'   => $schema, ]
        );

        $this->setField('name', $tableName);
        $this->setField('fields', $fieldNumber);
        $this->assertTrue($this->setField('spcname', 'pg_default'));
        $this->setField('tblcomment', 'Create auto!');

        // Clicks the button "next >" for inputing the detail information.
        //$this->assertTrue($this->ClickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->ClickSubmit('Next >'));

        for ($ii = 0; $ii < $fieldNumber; ++$ii) {
            $field     = 'field['.$ii.']';
            $type      = 'type['.$ii.']';
            $array     = 'array['.$ii.']';
            $fieldName = 'field'.$ii;

            // Enter the detail information of the table.
            $this->setField($field, $fieldName);
            $this->setField($type, 'text');
            $this->setField($array, '');
        }

        // Click the button "Create" for creating the
        // table use the specify conditions.
        $this->clickSubmit($lang['strcreate']);

        return true;
    }

    /**
     * Drop a exist table by the specified table name in a database.
     *
     * @param string $databaseName the database which the table content
     * @param string $tableName    the table name which wants to delete
     * @param string $schema       the schema the table belong
     */
    public function dropTable($databaseName, $tableName, $schema)
    {
        // Import the global variable.
        global $webUrl;
        global $lang, $SERVER;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        // Login and trun to the page which list all the
        // table in the database.
        $this->login(
            $SUPER_USER_NAME,
            $SUPER_USER_PASSWORD,
            "${webUrl}/login"
        );

        $this->get(
            "${webUrl}/tables",

            [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $databaseName,
                'schema'   => $schema,
                'table'    => $tableName, ]
        );

        // Click the button "Drop" for dropping the table from the database.
        $this->clickSubmit($lang['strdrop']);

        return true;
    }

    /**
     * Logout the system.
     */
    public function logout()
    {
        global $webUrl;
        global $lang;
        $this->get("${webUrl}/intro");

        // Select the frame and logout.
        $this->clickLink($lang['strlogout']);

        return true;
    }
}
