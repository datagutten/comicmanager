<?php


namespace datagutten\comicmanager\tests;


use datagutten\tools\PDOConnectHelper;
use PDO;
use PHPUnit\Framework\TestCase;

class common extends testCase
{
    /**
     * @var mixed
     */
    public $config;
    /**
     * @var PDO
     */
    public $db;
    /**
     * @var mixed
     */
    public $db_driver;

    public function setUp(): void
    {
        $this->config = require 'test_config.php';
        $this->db = PDOConnectHelper::connect_db_config($this->config['db']);
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->create_database();
    }
    public function tearDown(): void
    {
        $this->drop_database();
    }

    public function create_database()
    {
        if($this->db_driver!='sqlite')
            $this->db->query('CREATE DATABASE comicmanager_test');
    }

    public function drop_database()
    {
        if($this->db_driver!='sqlite')
            $this->db->query('DROP DATABASE comicmanager_test');
        else
        {
            unset($this->db);
            unlink($this->config['db']['db_file']);
        }
    }
}