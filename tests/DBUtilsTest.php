<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\DBUtils;

class DBUtilsTest extends common
{
    /**
     * @var DBUtils
     */
    private $utils;

    public function setUp(): void
    {
        parent::setUp();
        $this->utils = new DBUtils($this->db);
    }

    public function testTableExists()
    {
        $exist = $this->utils->tableExists('comicmanager_test', 'test_table');
        $this->assertFalse($exist);
        $this->db->query('CREATE TABLE `test_table` (`test_field` varchar(100) NOT NULL)');
        $exist = $this->utils->tableExists('comicmanager_test', 'test_table');
        $this->assertTrue($exist);
    }

    public function testHasColumn()
    {
        $exist = $this->utils->hasColumn('test_table', 'test_field');
        $this->assertFalse($exist);
        $this->db->query('CREATE TABLE `test_table` (`test_field` varchar(100) NOT NULL)');
        $exist = $this->utils->hasColumn('test_table', 'test_field');
        $this->assertTrue($exist);
    }

    public function testAddColumn()
    {
        $this->db->query('CREATE TABLE `test_table` (`test_field` varchar(100) NOT NULL)');
        $exist = $this->utils->hasColumn('test_table', 'test_field2');
        $this->assertFalse($exist);

        $this->utils->addColumn('test_table', 'test_field2', 'INT', 10);

        $exist = $this->utils->hasColumn('test_table', 'test_field2');
        $this->assertTrue($exist);
    }
}
