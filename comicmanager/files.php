<?php


namespace datagutten\comicmanager;


use FileNotFoundException;
use InvalidArgumentException;

class files
{
	/**
	 * @var string Path to comic image files
	 */
	public $file_path;
	public $debug = false;

	/**
	 * files constructor.
	 * @param string $file_path Path to image files
	 * @throws FileNotFoundException
	 */
	function __construct($file_path)
	{
		if(!file_exists($file_path))
			throw new FileNotFoundException($file_path);
		$this->file_path = realpath($file_path);
	}

	/**
     * Try different extensions for a file name
     * @param string $filename File name
     * @return string File name with extension
     * @throws comicsException File not found
     */
    public static function typecheck($filename)
    {
        $types = array('jpg', 'gif', 'png');
        foreach ($types as $type)
        {
            if (file_exists($filename . '.' . $type))
            {
                $file = $filename . ".$type";
                break;
            }
        }
        if (!isset($file)) //File not found
            throw new comicsException('Image not found by date: ' . $filename);

        return $file;
    }

	/**
	 * Get comic strips from files
	 *
	 * @param string $site slug
	 * @param string $filter_year
	 * @param string $filter_month
	 * @return array
	 * @throws comicsException
	 */
	public function releases_file_date($site, $filter_year=null, $filter_month=null)
	{
		if(!file_exists($base_path=$this->file_path.'/'.$site))
			throw new comicsException('No folder for site '.$site);

		$dir=scandir($base_path=$this->file_path.'/'.$site); //Get months
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

	function filename($site,$date,$create_dir=false)
	{
		//Files are stored in [filepath]/site/month/date
		if(empty($date) || !preg_match('/[0-9]{8}/', $date))
			throw new InvalidArgumentException('Invalid or empty date');

		$dir=$this->file_path.'/'.$site.'/'.substr($date,0,6);

		if($create_dir!==false && !file_exists($dir))
			mkdir($dir,0777,true);
		return $dir.'/'.$date;
	}
}