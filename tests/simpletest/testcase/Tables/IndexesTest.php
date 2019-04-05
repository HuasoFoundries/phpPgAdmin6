<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing INDEX feature in phpPgAdmin, including cases
 * for creating, clustering, reindexing and dropping indexes.
 *
 * @coversNothing
 */
class IndexesTest extends PreconditionSet
{
    /**
     * Set up the preconditon.
     */
    public function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_NAME;

        $this->login(
            $SUPER_USER_NAME,

            $SUPER_USER_NAME,
            "${webUrl}/login"
        );

        return true;
    }

    /**
     * Clean up all the result.
     */
    public function tearDown()
    {
        $this->logout();

        return true;
    }

    /**
     * TestCaseID: TCI01
     * Test creating indexes in a table.
     */
    public function testCreateIndex()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Indexes page
        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'create_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student', ])
        );

        // Set properties for the new index
        $this->assertTrue($this->setField('formIndexName', 'stu_name_idx'));
        $this->assertTrue($this->setField('TableColumnList', ['name']));
        $this->assertTrue($this->setField('IndexColumnList[]', 'name'));
        $this->assertTrue($this->setField('formIndexType', 'BTREE'));
        $this->assertTrue($this->setField('formUnique', false));
        $this->assertTrue($this->setField('formSpc', 'pg_default'));

        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        // Verify if the index is created correctly.
        $this->assertTrue($this->assertText($lang['strindexcreated']));

        return true;
    }

    /**
     * TestCaseID: TCI02
     * Cancel creating index.
     */
    public function testCancelCreateIndex()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Indexes page
        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'create_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student', ])
        );

        // Set properties for the new index
        $this->assertTrue($this->setField('formIndexName', 'stu_name_idx'));
        $this->assertTrue($this->setField('TableColumnList', ['name']));
        $this->assertTrue($this->setField('IndexColumnList[]', 'name'));
        $this->assertTrue($this->setField('formIndexType', 'BTREE'));
        $this->assertTrue($this->setField('formUnique', true));
        $this->assertTrue($this->setField('formSpc', 'pg_default'));

        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TRI01
     * Test reindexing an index in a table.
     */
    public function testReindex()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Indexes page
        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'reindex',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'index'    => 'stu_name_idx', ])
        );

        // Verify if the index is reindexed correctly.
        $this->assertTrue($this->assertText($lang['strreindexgood']));

        return true;
    }

    /**
     * TestCaseID: TCP01
     * Test clustering and analyzing the primary key in a table.
     */
    public function testClusterPrimaryKeyWithAnalyze()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'confirm_cluster_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'index'    => 'student_pkey', ])
        );
        $this->assertTrue($this->setField('analyze', true));
        $this->assertTrue($this->clickSubmit($lang['strcluster']));
        // Verify if the key is clustered correctly.
        $this->assertTrue($this->assertText($lang['strclusteredgood']));
        $this->assertTrue($this->assertText($lang['stranalyzegood']));

        return true;
    }

    /**
     * TestCaseID: TCP02
     * Test clustering the primary key without analyzing in a table.
     */
    public function testClusterPrimaryKeyWithoutAnalyze()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'confirm_cluster_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'index'    => 'student_pkey', ])
        );
        $this->assertTrue($this->setField('analyze', false));
        $this->assertTrue($this->clickSubmit($lang['strcluster']));
        // Verify if the key is clustered correctly.
        $this->assertTrue($this->assertText($lang['strclusteredgood']));

        return true;
    }

    /**
     * TestCaseID: TCP03
     * Test cancelling clustering the primary key in a table.
     */
    public function testCancelCluster()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'     => $SERVER,
                'action'     => 'confirm_cluster_index',
                'database'   => $DATABASE,
                'schema'     => 'public',
                'table'      => 'student',
                'constraint' => 'student_pkey', ])
        );
        $this->assertTrue($this->setField('analyze', true));
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TDI02
     * Cancel dropping an index in a table.
     */
    public function testCancelDropIndex()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'index'    => 'stu_name_idx', ])
        );
        $this->assertField($this->setField('cascade', false));
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TDI01
     * Test dropping an index in a table.
     */
    public function testDropIndex()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/indexes", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop_index',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'index'    => 'stu_name_idx', ])
        );
        $this->assertField($this->setField('cascade', true));
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify if the index is dropped correctly.
        $this->assertTrue($this->assertText($lang['strindexdropped']));

        return true;
    }
}
