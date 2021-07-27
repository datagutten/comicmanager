<?php


namespace datagutten\comicmanager\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\Queries;
use InvalidArgumentException;
use PDO;

/**
 * A comic strip, can have multiple releases
 * @package datagutten\comicmanager
 */
class Strip
{
    /**
     * @var Comic Comic object
     */
    public Comic $comic;
    /**
     * @var string Comic key field
     */
    public string $key_field;
    /**
     * @var string Comic key
     */
    public string $key;

    /**
     * @var Queries\Strip
     */
    private Queries\Strip $queries;
    /**
     * @var comicmanager
     */
    private comicmanager $comicmanager;
    private string $mode;


    public function __construct(Comic $comic, string $mode, comicmanager $comicmanager)
    {
        $this->comic = $comic;
        $this->mode = $mode;
        $this->comicmanager = $comicmanager;
        $this->queries = new Queries\Strip($comicmanager->config['db']);
    }

    /**
     * Get all releases of this strip
     * @return Release[]
     * @throws exceptions\StripNotFound
     */
    public function releases(): array
    {
        if ($this->mode === 'key')
            $st_releases = $this->queries->key($this->comic, $this->key, $this->key_field);
        else
            throw new InvalidArgumentException('Unable to fetch releases using mode ' . $this->mode);

        if($st_releases->rowCount() === 0)
            throw new exceptions\StripNotFound($this);

        return Releases::from_query($this->comicmanager, $st_releases);
    }

    /**
     * Get latest release
     * @return Release
     * @throws exceptions\StripNotFound
     */
    public function latest(): Release
    {
        $st = $this->queries->latest($this->comic, $this->key, $this->key_field);
        if($st->rowCount() === 0)
            throw new exceptions\StripNotFound($this);
        return new Release($this->comicmanager, $st->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Get strip by key
     * @param comicmanager $comicmanager
     * @param string $key
     * @param null $key_field
     * @return static
     */
    public static function from_grouping_key(comicmanager $comicmanager, string $key, $key_field=null): self
    {
        $strip = new static($comicmanager->info, 'key', $comicmanager);
        $strip->key = $key;
        $strip->key_field = $key_field ?? $comicmanager->info->key_field;
        return $strip;
    }

    /**
     * @param string $comic
     * @param string $key
     * @param string $key_field
     * @param comicmanager|null $comicmanager
     * @return static
     * @deprecated Use from_grouping_key
     */
    public static function from_key(string $comic, string $key, string $key_field = 'id', comicmanager $comicmanager = null): self
    {
        if(empty($comicmanager))
            $comicmanager = new comicmanager();
        $comic_obj = $comicmanager->comicinfo($comic);

        $strip = new static($comic_obj, 'key', $comicmanager);
        $strip->key = $key;
        $strip->key_field = $key_field;
        return $strip;
    }

    public function __toString(): string
    {
        return sprintf('%s %s %s', $this->comicmanager->info->name, $this->key_field, $this->key);
    }
}