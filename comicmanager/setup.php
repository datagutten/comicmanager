<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\elements\Comic;
use InvalidArgumentException;
use PDO;
use PDOStatement;

class setup extends Comic
{
    public PDO $db;
    public DBUtils $db_utils;

    public function __construct(array $values, array $config)
    {
        parent::__construct($values);
        $core = new core($config);
        $this->db = $core->db;
        $this->db_utils = $core->db_utils;
    }

    /**
     * Create the table comic_info with metadata for the comics
     * @return PDOStatement
     */
    function createComicInfoTable()
    {
        $q="CREATE TABLE `comic_info` (
              `id` varchar(45) NOT NULL,
              `name` varchar(45) NOT NULL,
              `keyfield` varchar(45) NOT NULL,
              `has_categories` int(1) NOT NULL DEFAULT 0,
              `possible_key_fields` varchar(45) NOT NULL,
              PRIMARY KEY (`id`)
            );
            ";
        return $this->db->query($q);
    }

    /**
     * Set the primary key field for a comic
     * @param string $key_field
     */
    function setKeyField(string $key_field)
    {
        if(!$this->db_utils->hasColumn($this->id, $key_field))
            $this->addKeyField($key_field);

        $st = $this->db->prepare('UPDATE comic_info SET keyfield = ? WHERE id=?');
        $st->execute(array($this->id, $key_field));
    }

    /**
     * Add a key field to a comic
     * @param $key_field
     */
    function addKeyField($key_field)
    {
        //$field_definitions=array('id'=>'INT(5)','customid'=>'INT(5)','original_date'=>'INT(11)');
        $lengths = array('id'=>5,'customid'=>5,'original_date'=>11);
        $comic = $this->id;
        Comic::validKeyField($key_field);
        if($this->db_utils->hasColumn($comic, $key_field))
            throw new InvalidArgumentException(sprintf('%s is already added as key field for %s', $key_field, $comic));
        $this->db_utils->addColumn($comic, $key_field, 'INT', $lengths[$key_field]);
        $st_key_fields = $this->db->prepare('SELECT possible_key_fields FROM comic_info WHERE id=?');
        $st_key_fields->execute([$comic]);
        $fields_string = $st_key_fields->fetch(PDO::FETCH_COLUMN);
        if(empty($fields_string))
            throw new InvalidArgumentException(sprintf('No metadata record for %s', $comic));
        $fields = self::appendKeyField($fields_string, $key_field);

        $st_update = $this->db->prepare('UPDATE comic_info SET possible_key_fields=? WHERE id=?');
        $st_update->execute(array($fields, $comic));
    }

    /**
     * Create a comic
     */
    public function create()
    {
        $comic = $this->id;
        if($this->db_utils->db_driver !== 'sqlite')
        {
            $q_comic = "CREATE TABLE `$comic` (
                  `date` int(11) DEFAULT NULL,
                  `site` varchar(45) NOT NULL,
                  `uid` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`uid`))";
        }
        else
        {
            $q_comic = "CREATE TABLE `$comic` (
                  `date` int(11) DEFAULT NULL,
                  `site` varchar(45) NOT NULL,
                  `uid` INTEGER PRIMARY KEY AUTOINCREMENT)";
        }
        $this->db->query($q_comic);

        $st_comic_info=$this->db->prepare("INSERT INTO comic_info (id,name,keyfield,possible_key_fields) VALUES (?,?,?,?)");
        $st_comic_info->execute(array($this->id, $this->name, $this->key_field, $this->key_field));
        $this->setKeyField($this->key_field);

        if($this->has_categories)
            $this->enableCategories();

        if(!empty($this->possible_key_fields))
        {
            foreach ($this->possible_key_fields as $field)
            {
                if($field==$this->key_field)
                    continue;
                $this->addKeyField($field);
            }
            $st_fields = $this->db->prepare('UPDATE comic_info SET possible_key_fields=? WHERE id=?');
            $fields_string = self::buildPossibleKeyFields($this->possible_key_fields);
            $st_fields->execute(array($fields_string, $comic));
        }
    }

    /**
     * Enable categories for a comic
     */
    function enableCategories()
    {
        $q_categories='CREATE TABLE `%s_categories` (
                      `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL,
                      `visible` int(1) NOT NULL
                    )';

        $this->db->query(sprintf($q_categories, $this->id));
        $this->db->query("ALTER TABLE $this->id ADD COLUMN category INT(2) NULL DEFAULT NULL");
        $st = $this->db->prepare('UPDATE comic_info SET has_categories = 1 WHERE id=?');
        $st->execute(array($this->id));
    }
}