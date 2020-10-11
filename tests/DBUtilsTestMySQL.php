<?php


namespace datagutten\comicmanager\tests;


use datagutten\comicmanager\DBUtils;
use datagutten\tools\PDOConnectHelper;
use PDO;

class DBUtilsTestMySQL extends DBUtilsTest
{
    public function setUp(): void
    {
        $this->config = require 'test_config_mysql.php';
        $this->create_database();
        $this->db = PDOConnectHelper::connect_db_config($this->config['db']);
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->utils = new DBUtils($this->db);
    }
}