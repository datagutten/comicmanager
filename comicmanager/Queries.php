<?php


namespace datagutten\comicmanager;


use PDO;
use PDOException;
use PDOStatement;

class Queries
{
    private PDO $db;
    /**
     * @var string Comic ID
     */
    private string $comic;

    /**
     * Queries constructor.
     * @param PDO $db PDO instance
     * @param string $comic Comic ID
     */
    public function __construct(PDO $db, string $comic)
    {
        $this->db = $db;
        $this->comic = core::clean_value($comic);
    }

    /**
     * Get releases by key
     * @param string $key_field Key field
     * @param string $key Key
     * @return PDOStatement
     * @throws PDOException
     */
    public function key(string $key_field, string $key): PDOStatement
    {
        $q = sprintf('SELECT * FROM %s WHERE %s=?',
            $this->comic,
            core::clean_value($key_field));
        $st = $this->db->prepare($q);
        $st->execute([$key]);
        return $st;
    }

    /**
     * Get latest release for a key
     * @param string $key_field Key field
     * @param string $key Key
     * @return PDOStatement
     * @throws PDOException
     */
    public function latest(string $key_field, string $key): PDOStatement
    {
        $q = sprintf('SELECT * FROM %s WHERE %s=? ORDER BY date DESC LIMIT 1', $this->comic, $key_field);
        $st_latest = $this->db->prepare($q);
        $st_latest->execute([$key]);
        return $st_latest;
    }

    /**
     * Get releases by key range
     * @param string $key_field Key field
     * @param string $from Key from
     * @param string $to Key to
     * @return PDOStatement
     * @throws PDOException
     */
    public function range(string $key_field, string $from, string $to): PDOStatement
    {
        $q = sprintf('SELECT * FROM %s WHERE %2$s>=? AND %2$s<=? GROUP BY %2$s ORDER BY %2$s',
            $this->comic,
            core::clean_value($key_field));
        $st = $this->db->prepare($q);
        $st->execute([$from, $to]);
        return $st;
    }

    /**
     * Get releases by date
     * @param string $site Site
     * @param string $date Date
     * @return PDOStatement
     */
    public function date(string $site, string $date): PDOStatement
    {
        $q = sprintf('SELECT * FROM %s WHERE site=? AND date=?', $this->comic);
        $st = $this->db->prepare($q);
        $st->execute([$site, $date]);
        return $st;
    }

    /**
     * Get releases by category
     * @param int $category Category ID
     * @return PDOStatement
     */
    public function category(int $category): PDOStatement
    {
        $q = sprintf('SELECT * FROM %s WHERE category=?', $this->comic);
        $st = $this->db->prepare($q);
        $st->execute([$category]);
        return $st;
    }
}