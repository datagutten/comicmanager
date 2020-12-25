<?php


namespace datagutten\comicmanager;


use InvalidArgumentException;
use PDO;
use PDOStatement;

class setup extends core
{
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
     * @param $comic
     * @param $key_field
     */
    function setKeyField($comic, $key_field)
    {
        if(!$this->db_utils->hasColumn($comic, $key_field))
            $this->addKeyField($comic, $key_field);

        $st = $this->db->prepare('UPDATE comic_info SET keyfield = ? WHERE id=?');
        $st->execute(array($comic, $key_field));
    }

    /**
     * Add a key field to a comic
     * @param string $comic Comic slug
     * @param $key_field
     */
    function addKeyField($comic, $key_field)
    {
        //$field_definitions=array('id'=>'INT(5)','customid'=>'INT(5)','original_date'=>'INT(11)');
        $lengths = array('id'=>5,'customid'=>5,'original_date'=>11);
        metadata::validateKeyField($key_field);
        if($this->db_utils->hasColumn($comic, $key_field))
            throw new InvalidArgumentException(sprintf('%s is already added as key field for %s', $key_field, $comic));
        $this->db_utils->addColumn($comic, $key_field, 'INT', $lengths[$key_field]);
        $st_key_fields = $this->db->prepare('SELECT possible_key_fields FROM comic_info WHERE id=?');
        $st_key_fields->execute([$comic]);
        $fields_string = $st_key_fields->fetch(PDO::FETCH_COLUMN);
        if(empty($fields_string))
            throw new InvalidArgumentException(sprintf('No metadata record for %s', $comic));
        $fields = metadata::appendKeyField($fields_string, $key_field);

        $st_update = $this->db->prepare('UPDATE comic_info SET possible_key_fields=? WHERE id=?');
        $st_update->execute(array($fields, $comic));
    }

    /**
     * Create a comic
     * @param string $comic Comic slug
     * @param string $name Comic name
     * @param string $key_field Primary grouping key
     * @param bool $has_categories Should the comic have categories?
     * @param array $possible_key_fields Extra grouping keys
     */
    function createComic($comic, $name, $key_field, $has_categories = false, $possible_key_fields = [])
    {
        $comic = core::clean_value($comic);
        metadata::validateKeyField($key_field);
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
        $st_comic_info->execute(array($comic, $name, $key_field, $key_field));
        $this->setKeyField($comic, $key_field);

        if($has_categories)
            $this->enableCategories($comic);

        if(!empty($possible_key_fields))
        {
            foreach ($possible_key_fields as $field)
            {
                if($field==$key_field)
                    continue;
                $this->addKeyField($comic, $field);
            }
            $st_fields = $this->db->prepare('UPDATE comic_info SET possible_key_fields=? WHERE id=?');
            $fields_string = metadata::buildPossibleKeyFields($possible_key_fields);
            $st_fields->execute(array($fields_string, $comic));
        }
    }

    /**
     * Enable categories for a comic
     * @param string $comic Comic slug
     */
    function enableCategories($comic)
    {
        $comic = core::clean_value($comic);
        $q_categories='CREATE TABLE `%s_categories` (
                      `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL,
                      `visible` int(1) NOT NULL
                    )';

        $this->db->query(sprintf($q_categories, $comic));
        $this->db->query("ALTER TABLE $comic ADD COLUMN category INT(2) NULL DEFAULT NULL");
        $st = $this->db->prepare('UPDATE comic_info SET has_categories = 1 WHERE id=?');
        $st->execute(array($comic));
    }
}