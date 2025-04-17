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

    /**
     * Get release information from database
     * @param elements\Release $release
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     * @throws exceptions\comicManagerException
     */
    public function get(elements\Release $release): Database\StatementInterface
    {
        $query = $this->selectQuery($release->comic);
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
        $query = $this->selectQuery($release->comic, 'uid')->where($query_fields);
        $st = $this->execute($query);
        if ($st->rowCount() == 0)
            return null;
        return $st->fetchColumn(0);
    }

    public function insert(elements\Release $release): Database\StatementInterface
    {
        $fields = self::filterFields($release->comic->fields, (array)$release);
        $query = $this->connection->insertQuery($release->comic->id)->insert(array_keys($fields))->values($fields);
        return $this->execute($query);
    }

    /**
     * Update release information in database
     * @param elements\Release $release Release object
     * @param bool $allow_empty
     * @return Database\StatementInterface|null
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\ComicInvalidArgumentException No valid fields
     */
    public function update(elements\Release $release, bool $allow_empty = false): ?Database\StatementInterface
    {
        $fields = self::filterFields($release->comic->fields, (array)$release, $allow_empty);
        $query = $this->connection->updateQuery($release->comic->id)->where(['uid' => $release->uid]);
        $in_db = $this->selectQuery($release->comic, array_keys($fields))
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
        $query = $this->selectQuery($comic, $comic->key_field)
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
        $query = $this->selectQuery($comic)
            ->whereNull($comic->key_field)
            ->where(['category' => $category]);

        return $this->execute($query);
    }

    /**
     * Get releases using wildcard for date and/or site
     * @param elements\Comic $comic Comic object
     * @param string $site Site slug with wildcards
     * @param string $date Date string with wildcards (YMD format)
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function wildcard(elements\Comic $comic, string $site, string $date): Database\StatementInterface
    {
        $query = $this->selectQuery($comic);

        if (str_contains($site, '%'))
            $query = $query->where(['site LIKE' => $site]);
        else
            $query = $query->where(['site' => $site]);


        if (str_contains($date, '%'))
            $query = $query->where(['date LIKE' => $date]);
        else
            $query = $query->where(['date' => $date]);

        $query = $query->order('date');

        return $this->execute($query);
    }
}