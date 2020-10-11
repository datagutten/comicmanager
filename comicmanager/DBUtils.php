<?php


namespace datagutten\comicmanager;


use InvalidArgumentException;
use PDO;
use PDOStatement;
use RuntimeException;

class DBUtils
{
    /**
     * @var PDO
     */
    private $db;
    /**
     * @var string Database driver
     */
    public $db_driver;

    function __construct(PDO $db)
    {
        $this->db = $db;
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Check if a table exists
     * @param string $database Database name
     * @param string $table Table name
     * @return bool
     */
    public function tableExists($database, $table)
    {
        if ($this->db_driver == 'mysql')
        {
            $q = 'SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?';
            $st = $this->db->prepare($q);
            $st->execute(array($database, $table));
            if ($st->rowCount() === 0)
                return false;
            else
                return true;
        } elseif ($this->db_driver == 'sqlite')
        {
            $q = 'SELECT name FROM sqlite_master WHERE type="table" AND name=?';
            $st = $this->db->prepare($q);
            $st->execute([$table]);
            return !empty($st->fetch());
        } else
            throw new RuntimeException('Unsupported database type ' . $this->db_driver);
    }

    /**
     * Check if a table has a column
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        if ($this->db_driver == 'mysql')
        {
            $st = $this->db->prepare(sprintf('SHOW COLUMNS FROM %s LIKE ?', core::clean_value($table)));
            $st->execute(array(core::clean_value($column)));
            if ($st->rowCount() === 1)
                return true;
            else
                return false;
        }
        elseif ($this->db_driver == 'sqlite')
        {
            $st = $this->db->prepare("SELECT COUNT(*) AS CNTREC FROM pragma_table_info(?) WHERE name=?");
            $st->execute(array(core::clean_value($table), $column));
            return $st->fetch(PDO::FETCH_COLUMN) == '1';
        } else
            throw new RuntimeException('Unsupported database type ' . $this->db_driver);
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