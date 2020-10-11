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
     * @var string
     */
    public $db_driver;

    public function setUp(): void
    {
        $this->config = require 'test_config.php';
        $this->drop_database();
        $this->create_database();
        $this->db = PDOConnectHelper::connect_db_config($this->config['db']);
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function create_database()
    {
        $config = $this->config['db'];
        if($config['db_type']!=='sqlite')
        {
            unset($config['db_name']);
            $db = PDOConnectHelper::connect_db_config($config);
            $db->query('CREATE DATABASE comicmanager_test');
        }
    }

    public function drop_database()
    {
        $config = $this->config['db'];
        if($config['db_type']!=='sqlite')
        {
            unset($config['db_name']);
            $db = PDOConnectHelper::connect_db_config($config);
            $db->query('DROP DATABASE comicmanager_test');
        }
        else
        {
            unset($this->db);
            if(file_exists($config['db_file']))
                unlink($config['db_file']);
        }
    }
}