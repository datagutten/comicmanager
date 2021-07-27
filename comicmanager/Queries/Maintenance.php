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
        $query = $this->connection->newQuery()
            ->select('*')->from($comic->id)->whereNotNull('id');
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
        $query = $this->connection->newQuery()
            ->select('id')->from($release->comic->id)->distinct('id')->where(['customid' => $release->id]);
        return $this->execute($query);
    }
}