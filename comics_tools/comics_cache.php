<?php


namespace datagutten\comics_tools;


use datagutten\comicmanager\core;
use InvalidArgumentException;
use PDO;

class comics_cache extends core
{
    /**
     * @var comics
     */
    public $comics;
    function __construct($site, $key)
    {
        parent::__construct();
        $this->comics=new comics($site, $key);
    }

    function create_cache()
    {
        $q = 'CREATE TABLE `comics_cache` (
                          `checksum` varchar(64) NOT NULL,
                          `slug` varchar(100) NOT NULL,
                          `date` varchar(100) NOT NULL,
                          `file` varchar(100) DEFAULT NULL,
                          `basename` varchar(100) DEFAULT NULL,
                          `site` varchar(100) NOT NULL,
                          UNIQUE KEY `checksum_site` (`checksum`,`site`,`date`),
                          KEY `slug_date_site` (`slug`,`date`,`site`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        $this->db->query($q);
    }

    /**
     * @param string $slug Comic slug
     * @param string $date Date in Y-M-D format
     * @return string Image file
     * @throws Exception Error from comics or invalid URL
     */
    function comics_release_single_cache($slug,$date)
    {
        if(strpos($date, '-')===false)
            throw new InvalidArgumentException('Date must be Y-M-D format');
        $st_select=$this->db->prepare("SELECT checksum,file,basename FROM comics_cache WHERE slug=? AND date=? AND site=?");
        $st_insert=$this->db->prepare("INSERT INTO comics_cache (checksum,slug,date,file,site) VALUES (?,?,?,?,?)");
        //Try to find image in local cache
        $this->db->execute($st_select, array($slug,$date,$this->comics->site));
        if($st_select->rowCount()==0)
        {
            $image_url=$this->comics->release_single($slug,$date); //Query comics to get image url
            //Extract image hash from URL
            preg_match(sprintf('^.+(%s.+/([a-f0-9]+)(?:_[A-Za-z0-9]+)?\..+)^', $slug), $image_url, $fileinfo);
            if(empty($fileinfo))
                throw new Exception('Invalid URL: '.$image_url);
            $st_insert->execute(array($fileinfo[2],$slug,$date,$fileinfo[1],$this->comics->site)); //Add image hash to local cache table
            return $image_url;
        }
        $cache = $st_select->fetch(PDO::FETCH_ASSOC);
        if(!empty($cache['file']))
            return $this->config['comics_media'].'/'.$cache['file'];
        else
            return sprintf('%s/%s/%s/%s', $this->config['comics_media'], $slug, $cache['checksum'][0], $cache['basename']);
    }
}