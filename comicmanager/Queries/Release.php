<?php


namespace datagutten\comicmanager\Queries;

use Cake\Database;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\exceptions;
use DateTime;
use PDO;

/**
 * Database queries for comic strip releases
 * @package datagutten\comicmanager\queries
 */
class Release extends Common
{
    public static function date(DateTime $date_obj): string
    {
        return $date_obj->format('Ymd');
    }

    /**
     * Get fields usable for querying a release
     * @param elements\Release $release
     * @return array
     * @throws exceptions\ComicInvalidArgumentException
     */
    public static function get_query_fields(elements\Release $release): array
    {
        if (!empty($release->uid))
            return ['uid' => $release->uid];
        elseif (!empty($release->site) && !empty($release->date))
            return ['site' => $release->site, 'date' => $release->date];
        else
            throw new exceptions\ComicInvalidArgumentException('No valid field combination found');
    }

    protected function selectQuery(elements\Comic $comic): Database\Query
    {
        return $this->connection->newQuery()->select('*')->from($comic->id);
    }

    /**
     * Get release information from database
     * @param elements\Release $release
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     * @throws exceptions\comicManagerException
     */
    public function get(elements\Release $release): Database\StatementInterface
    {
        $query = $this->connection->newQuery()->select('*')->from($release->comic->id);
        if (!empty($release->uid))
            $query = $query->where(['uid' => $release->uid]);
        else
        {
            $fields = self::filterFields($release->comic->fields, (array)$release);
            $query = $query->where($fields);
        }
        return $this->execute($query);
    }

    /**
     * Get release uid
     * @param elements\Release $release
     * @return ?int
     * @throws exceptions\ComicInvalidArgumentException
     * @throws exceptions\DatabaseException
     */
    public function get_uid(elements\Release $release): ?int
    {
        $query_fields = self::get_query_fields($release);
        $query = $this->selectQuery($release->comic)->where($query_fields);
        $st = $this->execute($query);
        if ($st->rowCount() == 0)
            return null;
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row['uid'];
    }

    public function insert(elements\Release $release): Database\StatementInterface
    {
        $fields = self::filterFields($release->comic->fields, (array)$release);
        $query = $this->connection->newQuery()->into($release->comic->id)->insert(array_keys($fields))->values($fields);
        return $this->execute($query);
    }

    /**
     * Update release information in database
     * @param elements\Release $release Release object
     * @return Database\StatementInterface|null
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\ComicInvalidArgumentException No valid fields
     */
    public function update(elements\Release $release): ?Database\StatementInterface
    {
        $fields = self::filterFields($release->comic->fields, (array)$release);
        $query = $this->connection->newQuery()->update($release->comic->id)->where(['uid' => $release->uid]);
        $in_db = $this->connection->newQuery()
            ->select(array_keys($fields))
            ->from($release->comic->id)
            ->where(['uid' => $release->uid])
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        $fields = static::compareFields($in_db, $release);
        if (empty($fields))
            return null;

        foreach ($fields as $key => $value)
        {
            $query->set($key, $value);
        }

        return $this->execute($query);
    }

    /**
     * Get releases by category
     * @param Comic $comic Comic object
     * @param int $category Category ID
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function category(Comic $comic, int $category): Database\StatementInterface
    {
        $query = $this->connection->newQuery()
            ->from($comic->id)
            ->select($comic->key_field)
            ->distinct($comic->key_field)
            ->whereNotNull($comic->key_field)
            ->where(['category' => $category])
            ->order($comic->key_field);
        return $this->execute($query);
    }

    /**
     * Get releases without key, but with category
     * @param Comic $comic Comic object
     * @param int $category Category ID
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function category_keyless(Comic $comic, int $category): Database\StatementInterface
    {
        $query = $this->connection->newQuery()
            ->from($comic->id)
            ->select('*')
            ->whereNull($comic->key_field)
            ->where(['category' => $category]);

        return $this->execute($query);
    }

    /**
     * Get releases with wildcard date
     * @param elements\Comic $comic Comic object
     * @param string $site Site slug
     * @param string $date Date string with wildcards (YMD format)
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function date_wildcard(elements\Comic $comic, string $site, string $date): Database\StatementInterface
    {
        $query = $this->connection->newQuery()
            ->from($comic->id)
            ->select('*')
            ->where(['site' => $site, 'date LIKE' => $date]);

        return $this->execute($query);
    }
}