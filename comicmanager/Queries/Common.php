<?php


namespace datagutten\comicmanager\Queries;

use Cake\Database;
use Cake\Database\Schema\TableSchema;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use PDO;
use PDOException;

class Common
{
    /**
     * @var ?Database\Connection
     */
    protected ?Database\Connection $connection;
    /**
     * @var Database\Schema\SchemaDialect
     */
    protected Database\Schema\SchemaDialect $schemaDialect;

    /**
     * Common constructor.
     * @param array $db_config Database configuration
     */
    public function __construct(array $db_config)
    {
        $driver = new Database\Driver\Mysql([
            'database' => $db_config['db_name'],
            'username' => $db_config['db_user'],
            'password' => $db_config['db_password'],
        ]);
        $this->connection = new Database\Connection([
            'driver' => $driver,
            'host' => $db_config['db_host'],
        ]);

        $this->schemaDialect = $this->connection->getDriver()->schemaDialect();
    }

    function __destruct()
    {
        $this->connection = null;
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    function quoteIdentifier(string $identifier): string
    {
        return $this->connection->getDriver()->quoteIdentifier($identifier);
    }

    /**
     * Check if a table exists
     * @param string $table Table name
     * @return bool
     * @throws exceptions\DatabaseException Database error
     */
    public function tableExists(string $table): bool
    {
        $sql = $this->schemaDialect->listTablesSql(['database' => $this->connection->getDriver()->schema()]);
        $tables = $this->query($sql[0])->fetchAll(PDO::FETCH_COLUMN);
        return array_search($table, $tables) !== false;
    }

    /**
     * Get columns in a table
     * @param string $table Table name
     * @return array Column names
     * @throws exceptions\DatabaseException Database error
     */
    public function columns(string $table): array
    {
        $info_q = $this->schemaDialect->describeColumnSql($table, [])[0];
        $st = $this->query($info_q);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Check if a table has a column with the given name
     * @param string $table Table name
     * @param string $column Column name
     * @return bool Column exists
     * @throws exceptions\DatabaseException Database error
     */
    public function hasColumn(string $table, string $column): bool
    {
        $columns = $this->columns($table);
        return array_search($column, $columns) !== false;
    }

    /**
     * Build SQL to add a column
     * @param TableSchema $schema TableSchema object
     * @param string $name Column name
     * @return string SQL query
     */
    public function addColumnSql(TableSchema $schema, string $name): string
    {
        $sqlPattern = 'ALTER TABLE %s ADD %s;';
        $column = $this->schemaDialect->columnSql($schema, $name);
        $tableName = $this->quoteIdentifier($schema->name());
        return sprintf($sqlPattern, $tableName, $column);
    }

    /**
     * Remove invalid fields
     * @param array $valid_fields Valid fields
     * @param array $values Key/value pairs
     * @param bool $allow_empty Set empty values to null instead of removing them
     * @return array Values of $values with keys in $valid_fields
     * @throws exceptions\ComicInvalidArgumentException No valid fields
     */
    public static function filterFields(array $valid_fields, array $values, bool $allow_empty = false): array
    {
        $fields = array_intersect_key($values, array_combine($valid_fields, $valid_fields));
        if (!$allow_empty)
            $fields = array_filter($fields);
        else
        {
            foreach ($fields as $key => $field)
            {
                if (empty($field))
                    $fields[$key] = null;
            }
        }
        if (empty($fields))
            throw new exceptions\ComicInvalidArgumentException('No valid fields');
        return $fields;
    }

    /**
     * Compare fields in database array with object properties and return an array with the difference
     * @param array $in_db Database values
     * @param elements\DatabaseObject $object Object to compare
     * @return array Different values
     */
    public static function compareFields(array $in_db, elements\DatabaseObject $object): array
    {
        $values_set = [];
        foreach ($in_db as $key => $db_value)
        {
            if ($object->$key != $db_value)
                $values_set[$key] = $object->$key;
        }
        return $values_set;
    }

    /**
     * A wrapper for query with custom exception
     * @param string $sql SQL query string
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function query(string $sql): Database\StatementInterface
    {
        try
        {
            return $this->connection->query($sql);
        }
        catch (PDOException $e)
        {
            throw new exceptions\DatabaseException($e->getMessage(), 0, $e);
        }
    }

    /**
     * A wrapper for execute with custom exception
     * @param Database\Query $query
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function execute(Database\Query $query): Database\StatementInterface
    {
        try
        {
            return $query->execute();
        }
        catch (PDOException $e)
        {
            throw new exceptions\DatabaseException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute SQL to create a table from a TableSchema object
     * @param TableSchema $schema TableSchema object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function createSchema(Database\Schema\TableSchema $schema): Database\StatementInterface
    {
        $sql = $schema->createSql($this->connection);
        return $this->query($sql[0]);
    }
}