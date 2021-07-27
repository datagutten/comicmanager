<?php


namespace datagutten\comicmanager\tests;


use datagutten\tools\PDOConnectHelper;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use datagutten\comicmanager\Queries;

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

    /**
     * @var Queries\ComicMetadata
     */
    protected Queries\ComicMetadata $queries_comic_meta;

    public function setUp(): void
    {
        $this->config = require __DIR__.'/test_config_mysql.php';
        try
        {
            $this->drop_database();
        }
        catch (PDOException $e)
        {
        }
        $this->create_database();
        $this->queries_comic_meta = new queries\ComicMetadata($this->config['db']);
        $this->db = PDOConnectHelper::connect_db_config($this->config['db']);
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $driver_version = $this->db->getAttribute(PDO::ATTR_CLIENT_VERSION);
        if ($this->db_driver === 'sqlite' && version_compare($driver_version, '3.16.0', '<'))
            $this->markTestSkipped(sprintf('sqlite must be version 3.16.0 or higher, current version is %s', $driver_version));
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