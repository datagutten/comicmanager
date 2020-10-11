<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\core;

class coreTest extends common
{
    public function testDBConfig()
    {
        $this->create_database();
        $core = new core();
        $this->assertSame('comicmanager_test', $core->db->db_name);
        $this->drop_database();
    }

    public function testClean_value()
    {
        $bad_value = 'test\\_string';
        $clean_value = core::clean_value($bad_value);
        $this->assertSame('test_string', $clean_value);
    }

    /*public function testAddColumn()
    {

    }

    public function test__construct()
    {

    }

    public function testTableExists()
    {

    }

    public function testHasColumn()
    {

    }*/
}
