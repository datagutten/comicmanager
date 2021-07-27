<?php


namespace datagutten\comicmanager\elements;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\Queries;
use PDO;
use PDOStatement;

class Strips extends ElementManager
{
    /**
     * @var Queries\Strip
     */
    private Queries\Strip $queries;

    function __construct(comicmanager $comicmanager)
    {
        parent::__construct($comicmanager);
        $this->queries = new Queries\Strip($comicmanager->config['db']);
    }

    public function from_query(PDOStatement $st)
    {
        while ($key = $st->fetch(PDO::FETCH_COLUMN))
        {
            $strip = Strip::from_grouping_key($this->comicmanager, $key);
            $releases[] = $strip->latest();
        }
    }

    /**
     * Get strip from grouping key
     * @param string $key
     * @param string|null $key_field
     * @return Strip
     */
    public function from_key(string $key, ?string $key_field = null): Strip
    {
        return Strip::from_grouping_key($this->comicmanager, $key, $key_field);
    }

    /**
     * Get highest and lowest value for a field
     * @param ?string $key_field Field name if not comic primary grouping key
     * @return array
     */
    function key_high_low(string $key_field = null): array
    {
        return $this->queries->key_high_low($this->comic, $key_field);
    }

    /**
     * Find first unused custom id
     * @return string
     * @throws exceptions\ComicInvalidArgumentException
     * @throws exceptions\DatabaseException
     */
    function next_customid(): string
    {
        $this->comic->allowedKeyField('customid');
        //throw new InvalidArgumentException(sprintf('%s does not have customid', $this->info['name']));
        $st = $this->queries->query("SELECT max(customid)+1 FROM {$this->comic['id']}");
        return $st->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * @param int $from
     * @param int $to
     * @param string|null $key_field
     * @return Strip[]
     */
    public function range(int $from, int $to, ?string $key_field = null): array
    {
        $strips = [];
        $range = $this->queries->range($this->comicmanager->info, $from, $to, $key_field);
        foreach ($range as $key)
        {
            $strips[] = Strip::from_grouping_key($this->comicmanager, $key, $key_field);
        }

        return $strips;
    }

}