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
        $q = 'SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?';
        $st = $this->db->prepare($q);
        $st->execute(array($this->config['db']['db_name'], $table));
        if ($st->rowCount() === 0)
            return false;
        else
            return true;
    }

    /**
     * Check if a table has a column
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        $st = $this->db->prepare(sprintf('SHOW COLUMNS FROM %s LIKE ?', core::clean_value($table)));
        $this->db->execute($st, array(core::clean_value($column)));
        if ($st->rowCount() === 1)
            return true;
        else
            return false;
    }

    /**
     * Add a column to a table
     * @param string $table Table name
     * @param string $column Column name
     * @param string $type Data type
     * @param int $length Field length
     * @return PDOStatement
     */
    public function addColumn($table, $column, $type, $length)
    {
        $valid_types = ['VARCHAR', 'INT'];
        if (array_search($type, $valid_types) === false)
            throw new InvalidArgumentException('Invalid column type: ' . $type);
        if (!is_numeric($length))
            throw new InvalidArgumentException('Length is not numeric');

        /** @noinspection SyntaxError */
        return $this->db->query(sprintf('ALTER TABLE %s ADD COLUMN `%s` %s(%d) DEFAULT NULL',
            core::clean_value($table), core::clean_value($column), $type, $length));
    }
}