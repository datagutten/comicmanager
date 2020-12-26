<?php


namespace datagutten\comicmanager;

// A comic strip, can have multiple releases
use InvalidArgumentException;
use PDO;

class Strip
{
    /**
     * @var string Comic ID
     */
    public string $comic;
    /**
     * @var string Comic key field
     */
    public string $key_field;
    /**
     * @var string Comic key
     */
    public string $key;

    /**
     * @var Queries
     */
    private Queries $queries;
    /**
     * @var comicmanager
     */
    private comicmanager $comicmanager;
    private string $mode;


    public function __construct(string $comic, string $mode, comicmanager $comicmanager)
    {
        $this->comic = $comic;
        $this->mode = $mode;
        $this->comicmanager = $comicmanager;
        $this->queries = new Queries($comicmanager->db, $comic);
    }

    /**
     * @return release[]
     */
    public function releases(): array
    {
        if ($this->mode === 'key')
            $st_releases = $this->queries->key($this->key_field, $this->key);
        else
            throw new InvalidArgumentException('Unable to fetch releases using mode ' . $this->mode);

        $releases = [];
        while ($release = $st_releases->fetch(PDO::FETCH_ASSOC))
        {
            $releases[] = new release($this->comicmanager, $release);
        }
        return $releases;
    }

    /**
     * Get latest release
     * @return release
     */
    public function latest(): release
    {
        $st = $this->queries->latest($this->key_field, $this->key);
        return new release($this->comicmanager, $st->fetch(PDO::FETCH_ASSOC));
    }

    public static function from_key(string $comic, $key, $key_field = 'id', comicmanager $comicmanager=null)
    {
        if(empty($comicmanager))
            $comicmanager = new comicmanager();
        $comicmanager->comicinfo($comic);

        $strip = new static($comic, 'key', $comicmanager);
        $strip->key = $key;
        $strip->key_field = $key_field;
        return $strip;
    }

    public function __toString(): string
    {
        return sprintf('%s %s %s', $this->comic, $this->key_field, $this->key);
    }
}