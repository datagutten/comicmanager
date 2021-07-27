<?php


namespace datagutten\comicmanager;


/**
 * SQL connection and database utilities
 * @package datagutten\comicmanager
 */
class core
{
    /**
     * @var Queries\Common
     */
    public Queries\Common $db;
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

        $this->db = new Queries\Common($config['db']);

        if(isset($this->config['debug']) && $this->config['debug']===true)
            $this->debug = true;
    }
}