<?php


namespace datagutten\comicmanager\tests;


use datagutten\comicmanager\setup;
use pdo_helper;
use PHPUnit\Framework\TestCase;

class common extends testCase
{
    public function setUp(): void
    {
        copy(__DIR__ . '/test_config_db.php', __DIR__.'/config_db.php');
        set_include_path(__DIR__);
    }
    public function tearDown(): void
    {
        unlink(__DIR__.'/config_db.php');
        //$this->class->db->query('DROP DATABASE comicmanager_test');
    }

    public function create_database()
    {
        $config = require 'test_config_db.php';
        $db = new pdo_helper;
        $db->connect_db($config['db_host'], '', $config['db_user'], $config['db_password'], $config['db_type']);
        $db->query('CREATE DATABASE comicmanager_test');
    }

    public function drop_database()
    {
        $setup = new setup();
        $setup->db->query('DROP DATABASE comicmanager_test');
    }
}