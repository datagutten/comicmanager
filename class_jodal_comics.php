<?Php
class comics
{
	public $image_host;
	public $ch;
	public $site;
	function __construct($site,$key)
	{
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Accept: application/json',"Authorization: Key $key"));

		if(substr($site,-1,1)=='/') //Remove trailing slash from site url
			$this->site=substr($site,0,-1);
		else
			$this->site=$site;
	}
	function request($uri)
	{
		curl_setopt($this->ch,CURLOPT_URL,$this->site.$uri);
		$data=curl_exec($this->ch);
		$code=curl_getinfo($this->ch,CURLINFO_HTTP_CODE); //Get HTTP return code
		if($data===false)
		{
			trigger_error("cURL error: ".curl_error($this->ch),E_USER_WARNING);
			return false;
		}
		elseif($code==400)
		{
			trigger_error("Bad request, check parameters",E_USER_WARNING);
			return false;
		}
		elseif($code==401)
		{
			trigger_error("Invalid secret key",E_USER_ERROR);
			return false;
		}
		elseif(empty($data))
		{
			trigger_error("No data returned",E_USER_WARNING);
			return false;
		}
		else
			return json_decode($data,true);
	}
	function releases($slug,$year,$limit=365) //Henting av bilder fra jodal
	{
		if(strlen($year)!=4 || !is_numeric($year))
		{
			trigger_error("Year should be four digits",E_USER_WARNING);
			return false;
		}
		$releases=$this->request("/api/v1/releases/?comic__slug=$slug&pub_date__year=$year&limit=$limit");
		if($releases===false)
			return false;
		foreach(array_reverse($releases['objects']) as $release)
		{
			$rows[]=array('date'=>str_replace('-','',$release['pub_date']),'file'=>$release['images'][0]['file']);
		}
		return $rows;
	}
	function release_single($slug,$date)
	{
		$release=$this->request("/api/v1/releases/?comic__slug=$slug&pub_date=$date");

		if($release['meta']['total_count']==0)
			return false;
		else
			return $release['objects'][0]['images'][0]['file'];
	}
}