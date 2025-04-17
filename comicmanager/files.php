<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\exceptions\ImageNotFound;
use datagutten\tools\files\files as file_tools;
use DateTime;
use FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class files
{
	/**
	 * @var string Path to comic image files
	 */
	public $file_path;
	public bool $debug = false;
    public Filesystem $filesystem;

    /**
     * files constructor.
     * @param string $file_path Path to image files
     * @throws FileNotFoundException File path not found
     */
	function __construct(string $file_path)
	{
        $this->filesystem = new Filesystem();
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
     * Get path to site folder
     * @param string $site
     * @return string Site folder path
     */
    public function site(string $site): string
    {
        return file_tools::path_join($this->file_path, $site);
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
                if (str_ends_with($file, 'txt'))
                    continue;
                if (preg_match('#^(\d{8})\.\w+#', $file, $matches_date)) //Get date from file name
				{
                    $title_file = file_tools::path_join($base_path, $month, $matches_date[1] . '.txt');
                    if (file_exists($title_file))
                        $title = file_get_contents($title_file);
                    else
                        $title = null;

                    $releases[] = [
                        'date' => $matches_date[1],
                        'image_file' => file_tools::path_join($month, $file),
                        'title' => $title,
                    ];
				}
			}
		}

		return $releases;
	}

    /**
     * Get file name
     * @param string $site Site slug
     * @param DateTime $date Date
     * @param false $create_dir Create folder if not exists
     * @return string File path and name without extension
     */
    function filename(string $site, DateTime $date, bool $create_dir = false): string
    {
		//Files are stored in [filepath]/site/month/date
        $dir = file_tools::path_join($this->file_path, $site, $date->format('Ym'));

		if($create_dir!==false && !file_exists($dir))
			mkdir($dir,0777,true); //TODO: Use symfony/filesystem
		return $dir.'/'.$date->format('Ymd');
	}
}