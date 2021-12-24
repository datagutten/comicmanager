<?php


namespace datagutten\comicmanager\maintenance;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\Queries;
use PDO;

class Maintenance
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    public elements\Comic $comic;
    /**
     * @var Queries\Maintenance
     */
    private Queries\Maintenance $queries;

    function __construct(comicmanager $comicmanager)
    {
        $this->comicmanager = $comicmanager;
        $this->comic = $this->comicmanager->info;
        $this->queries = new Queries\Maintenance($this->comicmanager->config['db']);
    }

    /**
     * Propagate category to all releases of a strip
     * @return string[] Output lines
     * @throws exceptions\InvalidMaintenanceTool|exceptions\comicManagerException
     */
    function propagateCategories(): array
    {
        if (!$this->comicmanager->info->has_categories)
            throw new exceptions\InvalidMaintenanceTool(sprintf('Comic do not have categories'));
        $output = [];
        $comic = $this->comicmanager->info;
        $table = $this->comicmanager->info->id;
        $keyfield = $this->comicmanager->info->key_field;

        $q = sprintf('SELECT %1$s,category FROM %2$s WHERE category IS NOT NULL AND %1$s IS NOT NULL GROUP BY %1$s', $keyfield, $comic->id);

        $st_strips = $this->queries->query($q); //Get all unique strips with category
        $categories = $st_strips->fetchAll(PDO::FETCH_KEY_PAIR);

        $st_missing = $this->queries->query("SELECT * FROM $table WHERE $keyfield IS NOT NULL AND category IS NULL");

        foreach ($st_missing->fetchAll(PDO::FETCH_ASSOC) as $strip)
        {
            if (isset($categories[$strip[$keyfield]]))
            {
                $this->comicmanager->releases->save(['uid' => $strip['uid'], 'category' => $categories[$strip[$keyfield]]]);
                $output[] = sprintf('Set category to %d for uid %d', $categories[$strip[$keyfield]], $strip['uid']);
            }
        }
        return $output;
    }

    /**
     * Propagate id to all releases of a strip
     * @return string[] Output lines
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\InvalidMaintenanceTool Maintenance tool is not valid for this comic
     */
    function propagateId(): array
    {
        try
        {
            $this->comic->allowedKeyField('id');
        }
        catch (exceptions\ComicInvalidArgumentException $e)
        {
            throw new exceptions\InvalidMaintenanceTool($e->getMessage(), $e->getCode(), $e);
        }
        if ($this->comic->key_field == 'id')
            throw new exceptions\InvalidMaintenanceTool('This tool is only useful for comics using an alternate key field');

        $st_keys = $this->queries->idGroupId($this->comic);
        $keys = $st_keys->fetchAll(PDO::FETCH_KEY_PAIR);
        $st_missing = $this->queries->missingId($this->comic);

        $output = [];
        foreach ($st_missing->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            try
            {
                $release = new elements\Release($this->comicmanager, $row, false);
            }
            catch (exceptions\comicManagerException $e)
            {
                $output[] = sprintf('Error creating release object for uid %d', $row['uid']);
                continue;
            }
            $key = $release->key();
            if (isset($keys[$key]))
            {
                $release->id = $keys[$key];
                try
                {
                    $release->save(false);
                    $output[] = sprintf('Set id to %s for uid %d with %s %s', $release->id, $release->uid, $this->comic->key_field, $release->key());
                }
                catch (exceptions\comicManagerException | exceptions\DatabaseException $e)
                {
                    $output[] = sprintf('Error setting id to %s for uid %d with %s %s: %s', $release->id, $release->uid, $this->comic->key_field, $release->key(), $e->getMessage());
                }
            }
        }
        return $output;
    }

    /**
     * @return string[] Output lines
     * @throws exceptions\ComicInvalidArgumentException This tool is only useful for comics using customid
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\InvalidMaintenanceTool
     * @throws exceptions\comicManagerException
     */
    function idToCustomId(): array
    {
        $output = [];

        if ($this->comic->key_field == 'id')
            throw new exceptions\InvalidMaintenanceTool('This tool is only useful for comics using an alternate key field');

        $st = $this->queries->idNotLikeCustomId($this->comic);
        $releases = elements\Releases::from_query($this->comicmanager, $st);

        foreach($releases as $release_obj)
        {
            //$output[] = '<pre>' . print_r($release_obj, true) . '</pre>';
            //Find releases with customid similar to this release id
            $st_custom_id = $this->queries->differentIdForCustomId($release_obj);

            $count = $st_custom_id->rowCount();
            if ($count > 1)
            {
                $output[] = sprintf("Multiple ids for customid %d", $release_obj->customid);
                while ($row_id = $st_custom_id->fetch())
                {
                    $output[] = $row_id[0];
                }
                continue;
            }
            elseif ($count == 1)
            {
                $release = $st_custom_id->fetch('assoc');
                if ($release['id'] != $release_obj->id) //Check if the matching release has the same id
                {
                    $output[] = sprintf("Release with customid %d has different id: %d", $release_obj->id, $release['id']);
                    continue;
                } else
                    $output[] = sprintf("Release with customid %d and uid %d has same id as customid %d", $release_obj['customid'], $release_obj['uid'], $release_obj['id']);
            }
            else
            {
                $output[] = sprintf("Customid %d is free", $release_obj['id']);
            }
            try
            {
                $release_obj->customid = $release_obj->id;
                $release_obj->save(false);
            }
            catch (exceptions\comicManagerException $e)
            {
                $output[] = $e->getMessage();
            }
        }

        return $output;
    }
}