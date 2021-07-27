<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\exceptions\ImageNotFound;
use FileNotFoundException;

class files
{
	/**
	 * @var string Path to comic image files
	 */
	public $file_path;
	public bool $debug = false;

    /**
     * files constructor.
     * @param string $file_path Path to image files
     * @throws FileNotFoundException File path not found
     */
	function __construct(string $file_path)
	{
		if(!file_exists($file_path))
			throw new FileNotFoundException($file_path);
		$this->file_path = realpath($file_path);
	}

    /**
     * Try different extensions for a file name
     * @param string $filename File name
     * @return string File name with extension
     * @throws ImageNotFound File not found
     */
    public static function typecheck(string $filename): string
    {
        $types = array('jpg', 'jpeg', 'gif', 'png');
        foreach ($types as $type)
        {
            if (file_exists($filename . '.' . $type))
            {
                $file = $filename . ".$type";
                break;
            }
        }
        if (!isset($file)) //File not found
            throw new ImageNotFound('Image not found: ' . $filename);

        return $file;
    }

    /**
     * Get comic strips from files
     * @param string $site Site slug
     * @param null $filter_year Year
     * @param null $filter_month Month
     * @return array
     * @throws exceptions\ComicInvalidArgumentException No folder for site
     */
	public function releases_file_date(string $site, $filter_year=null, $filter_month=null): array
    {
		if(!file_exists($base_path=$this->file_path.'/'.$site))
			throw new exceptions\ComicInvalidArgumentException('No folder for site '.$site);

		$dir=scandir($base_path=$this->file_path.'/'.$site); //Get months
        if($dir===false)
            return [];
		$dir=array_diff($dir,array('.','..','Thumbs.db'));
		$releases = array();
		foreach ($dir as $month)
		{
			if(!is_dir($base_path.'/'.$month))
			{
				if($this->debug)
					echo sprintf('Month folder %s/%s is not a directory', $base_path, $month);
				continue;
			}

			if($filter_year!==false && substr($month,0,4)!=$filter_year) //Filter by year
				continue;
			if($filter_month!==false && substr($month,4,2)!=$filter_month) //Filter by month
				continue;

			$month_dir=scandir($base_path.'/'.$month); //Get the files for this month
            if ($month_dir===false)
                continue;

			foreach ($month_dir as $file)
			{
				if(preg_match('^[0-9]+^',$file,$date)) //Get date from file name
				{
					$file_info['date']=$date[0];
					$file_info['file']=$base_path.'/'.$month.'/'.$file;
					$releases[]=$file_info;
				}
			}
		}

		return $releases;
	}

    /**
     * Get file name
     * @param string $site Site slug
     * @param string $date Date
     * @param false $create_dir Create folder if not exists
     * @return string File path and name without extension
     * @throws exceptions\ComicInvalidArgumentException Invalid or empty date
     */
    function filename(string $site, string $date, $create_dir = false): string
    {
		//Files are stored in [filepath]/site/month/date
		if(empty($date) || !preg_match('/[0-9]{8}/', $date))
			throw new exceptions\ComicInvalidArgumentException('Invalid or empty date');

		$dir=$this->file_path.'/'.$site.'/'.substr($date,0,6);

		if($create_dir!==false && !file_exists($dir))
			mkdir($dir,0777,true);
		return $dir.'/'.$date;
	}
}