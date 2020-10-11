<?php


namespace datagutten\comicmanager;


use datagutten\tools\PDOConnectHelper;
use FileNotFoundException;
use InvalidArgumentException;
use PDO;
use pdo_helper;
use PDOException;
use PDOStatement;

/**
 * SQL connection and database utilities
 * @package datagutten\comicmanager
 */
class core
{
    /**
     * @var PDO
     */
    public $db;
    /**
     * @var array Configuration parameters
     */
    public $config;
    /**
     * @var bool Show debug output
     */
    public $debug = false;
    /**
     * @var DBUtils
     */
    public $db_utils;

    /**
     * core constructor.
     * @param array|null $config Configuration parameters
     */
    function __construct(array $config=null)
    {
        if(get_include_path()=='.:/usr/share/php')
            set_include_path(__DIR__);

        if(empty($config))
            $this->config = require 'config.php';
        else
            $this->config = $config;

        $this->db = PDOConnectHelper::connect_db_config($config['db']);
        $this->db_utils = new DBUtils($this->db);

        if(isset($this->config['debug']) && $this->config['debug']===true)
            $this->debug = true;
    }

    /**
     * Make a value safe for SQL by removing all other characters than a-z 0-9 _
     * @param string $value Value to be cleaned
     * @return string
     */
    public static function clean_value($value)
    {
        return preg_replace('/[^a-z0-9_]+/', '', $value);
    }

    /**
     * Check if a table exists
     * @param string $table Table name
     * @return bool
     */
    public function tableExists($table)
    {
        return $this->db_utils->tableExists($this->config['db']['db_name'], $table);
    }
}