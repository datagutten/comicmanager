<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\DBUtils;
use PDO;

class DBUtilsTest extends common
{
    /**
     * @var DBUtils
     */
    protected $utils;

    public function setUp(): void
    {
        parent::setUp();
        $this->utils = new DBUtils($this->db);
        $version = $this->db->getAttribute(PDO::ATTR_CLIENT_VERSION);
        printf("sqlite version %s\n", $version);
    }

    public function testDBType()
    {
        $this->assertEquals($this->config['db']['db_type'], $this->utils->db_driver);
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
        $this->db->query('CREATE TABLE `test_table` (`test_field` varchar(100) NOT NULL)');
        $exist = $this->utils->hasColumn('test_table', 'test_field_bad');
        $this->assertFalse($exist);
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
