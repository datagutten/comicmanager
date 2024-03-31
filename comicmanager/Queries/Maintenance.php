<?php


namespace datagutten\comicmanager\Queries;

use Cake\Database;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;

class Maintenance extends Common
{
    /**
     * Get releases where id is not like customid
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\ComicInvalidArgumentException Customid is not a valid key field for the comic
     */
    function idNotLikeCustomId(elements\Comic $comic): Database\StatementInterface
    {
        $comic->allowedKeyField('customid');
        $query = $this->connection
            ->selectQuery('*', $comic->id)->whereNotNull('id');
        $q = $query->sql() . ' AND (customid IS NULL OR id!=customid) ORDER BY id';
        return $this->query($q);
    }

    /**
     * Check if release id is used as customid on other releases
     * @param elements\Release $release
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    function differentIdForCustomId(elements\Release $release): Database\StatementInterface
    {
        $query = $this->connection
            ->selectQuery('id', $release->comic->id)->distinct('id')->where(['customid' => $release->id])->andWhere(['id IS NOT NULL']);
        return $this->execute($query);
    }

    /**
     * Get all printed ids and grouping ids
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function idGroupId(elements\Comic $comic): Database\StatementInterface
    {
        $query = $this->connection
            ->selectQuery([$comic->key_field, 'id'], $comic->id)->whereNotNull('id')->whereNotNull($comic->key_field)->group($comic->key_field);
        return $this->execute($query);
    }

    /**
     * Get releases where grouping key is set, but not printed id
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function missingId(elements\Comic $comic): Database\StatementInterface
    {
        $query = $this->connection
            ->selectQuery('*', $comic->id)->whereNotNull($comic->key_field)->whereNull('id');
        return $this->execute($query);
    }

    /**
     * Get releases where key and category is set
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function releasesWithKeyAndCategories(elements\Comic $comic): Database\StatementInterface
    {
        $query = $this->connection
            ->selectQuery([$comic->key_field, 'category'], $comic->id)->whereNotNull($comic->key_field)->whereNotNull('category');
        return $this->execute($query);
    }
}