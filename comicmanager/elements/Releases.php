<?php


namespace datagutten\comicmanager\elements;


use Cake\Database;
use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\Queries;
use PDO;

class Releases extends ElementManager
{
    /**
     * @var Queries\Release
     */
    private Queries\Release $queries_cake;

    public function __construct(comicmanager $comicmanager)
    {
        parent::__construct($comicmanager);
        $this->queries_cake = new Queries\Release($comicmanager->config['db']);
    }

    /**
     * Fetch releases from a statement and create Release objects
     * @param comicmanager $comicmanager
     * @param Database\StatementInterface $st
     * @return Release[]
     * @throws exceptions\comicManagerException
     */
    public static function from_query(comicmanager $comicmanager, Database\StatementInterface $st): array
    {
        $releases = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC))
        {
            $releases[] = new Release($comicmanager, $row);
        }
        return $releases;
    }

    /**
     * Show releases in a category, using the latest release for each strip
     * @param int $category Category ID
     * @return Release[] Array of Strip objects
     * @throws exceptions\StripNotFound
     */
    public function category(int $category): array
    {
        $releases = [];
        $st = $this->queries_cake->category($this->comic, $category);

        while ($key = $st->fetch(PDO::FETCH_COLUMN))
        {
            $strip = Strip::from_grouping_key($this->comicmanager, $key);
            $releases[] = $strip->latest();
        }

        $st = $this->queries_cake->category_keyless($this->comic, $category);

        $releases = array_merge($releases, $this->from_query($this->comicmanager, $st));
        return $releases;
    }

    /**
     * Return a new release object
     * @param array $fields
     * @param bool $load_image
     * @return Release
     * @throws exceptions\comicManagerException
     */
    public function release(array $fields, bool $load_image = true): Release
    {
        return new Release($this->comicmanager, $fields, $load_image);
    }

    /**
     * Return a new release object
     * @param array $fields
     * @param bool $load_image
     * @return Release
     * @throws exceptions\comicManagerException
     */
    public function get(array $fields, bool $load_image = true): Release
    {
        $release = $this->release($fields, $load_image);
        $release->load_db();
        return $release;
    }

    /*    public function get_multiple(array $fields, bool $load_image = true): array
        {
            $releases = $this->queries_cake->get(new Release($this->comicmanager, $fields));
        }*/

    /**
     * Create and save a release
     * @param array $fields
     * @return Release
     * @throws exceptions\comicManagerException
     */
    public function save(array $fields): Release
    {
        $release = $this->release($fields, false);
        $release->save();
        return $release;
    }

    /**
     * @param string $site
     * @param string $date
     * @return Release[]
     * @throws exceptions\comicManagerException
     */
    public function date_wildcard(string $site, string $date): array
    {
        $st = $this->queries_cake->date_wildcard($this->comic, $site, $date);
        return $this->from_query($this->comicmanager, $st);
    }
}