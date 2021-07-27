<?php


namespace datagutten\comicmanager;


use datagutten\tools\PDOConnectHelper;
use PDO;

/**
 * SQL connection and database utilities
 * @package datagutten\comicmanager
 */
class core
{
    /**
     * @var PDO
     * @deprecated
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
            $this->config = require __DIR__.'/../config.php';
        else
            $this->config = $config;

        $this->db = PDOConnectHelper::connect_db_config($config['db']);

        if(isset($this->config['debug']) && $this->config['debug']===true)
            $this->debug = true;
    }
}