<?Php

namespace datagutten\comics_tools;

use datagutten\comics_tools\exceptions;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;

class comics
{
	public $image_host;
	public $ch;
	public $site;
	public $error;
	public $secret_key;
	function __construct($site,$key)
	{
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Accept: application/json',"Authorization: Key $key"));
		$this->secret_key=$key;
		if(substr($site,-1,1)=='/') //Remove trailing slash from site url
			$this->site=substr($site,0,-1);
		else
			$this->site=$site;
	}

    /**
     * Do a request to the comics API
     * @param string $uri Relative URL to get from comics
     * @return array Releases
     * @throws exceptions\HTTPError HTTP error
     * @throws exceptions\ReleaseNotFound No releases found
     */
	function request($uri)
	{
		curl_setopt($this->ch,CURLOPT_URL,$this->site.$uri.'&key='.$this->secret_key);
		$data=curl_exec($this->ch);
		$code=curl_getinfo($this->ch,CURLINFO_HTTP_CODE); //Get HTTP return code
		if($data===false)
			throw new exceptions\HTTPError('cURL error: '.curl_error($this->ch));
		elseif($code==400)
            throw new exceptions\HTTPError('Bad request, check parameters');
		elseif($code==401)
            throw new exceptions\HTTPError('Invalid secret key');
		elseif(empty($data))
            throw new exceptions\HTTPError('Comics returned empty response');

		$releases=json_decode($data,true);
		if(empty($releases['objects']))
		{
            throw new exceptions\ReleaseNotFound(sprintf('No releases found for query %s on site %s',
                basename($uri),
                $this->site));
		}
		else
			return $releases;
	}

    /**
     * Get all releases from a year
     * @param string $slug Comic slug
     * @param int $year Year
     * @return array Releases
     * @throws Exception Request failed
     */
	function releases_year($slug,$year)
	{
		if(strlen($year)!=4 || !is_numeric($year))
			throw new InvalidArgumentException('Year must be four digits');
		$releases=$this->request("/api/v1/releases/?comic__slug=$slug&pub_date__year=$year&limit=366");
		return $this->format_releases($releases);
	}

    /**
     * Get all releases from a month
     * @param string $slug Comic slug
     * @param int $year Year
     * @param int $month Month
     * @return array Releases
     * @throws Exception Request failed
     */
	function releases_month($slug,$year,$month)
	{
		list($start,$end)=$this->month($month,$year);

		if(strlen($year)!=4 || !is_numeric($year))
            throw new InvalidArgumentException('Year must be four digits');

		$releases=$this->request("/api/v1/releases/?comic__slug=$slug&pub_date__gte=$start&pub_date__lte=$end&limit=31");
        return $this->format_releases($releases);
	}

    /**
     * Get a single release
     *
     * @param string $slug Comic slug
     * @param string $date Release date
     * @return string File name
     * @throws exceptions\HTTPError HTTP error
     * @throws exceptions\ReleaseNotFound No release found
     */
    function release_single($slug, $date)
	{
		$release=$this->request("/api/v1/releases/?comic__slug=$slug&pub_date=$date");
		if($release['meta']['total_count']==0) //TODO: Check if this is hit
			throw new exceptions\ReleaseNotFound('No release found');
		else
			return $release['objects'][0]['images'][0]['file'];
	}

    /**
     * Make the release array structure similar to the file functions
     *
     * @param array $releases
     * @return array
     */
    function format_releases($releases)
	{
	    $rows = array();
		foreach(array_reverse($releases['objects']) as $release)
		{
			$rows[]=array('date'=>str_replace('-','',$release['pub_date']),'file'=>$release['images'][0]['file']);
		}
		return $rows;
	}
	private function month($month,$year) //Find first and last day of month
	{
		$start=new DateTime("$year-$month-1");
		$end=clone $start;
		$end->add(new DateInterval('P1M')); //Add 1 month
		$end->sub(new DateInterval('P1D')); //Subtract 1 day to get last day of month

		return array($start->format('Y-m-d'),$end->format('Y-m-d'));
		//list($start,$end)=$this->month($month,$year)
	}
}