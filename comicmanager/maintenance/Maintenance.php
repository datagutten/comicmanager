<?php


namespace datagutten\comicmanager\maintenance;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use PDO;

class Maintenance
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    public elements\Comic $comic;

    function __construct(comicmanager $comicmanager)
    {
        $this->comicmanager = $comicmanager;
        $this->comic = $this->comicmanager->info;
    }

    /**
     * Propagate category to all releases of a strip
     * @return string[] Output lines
     * @throws exceptions\InvalidMaintenanceTool
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

        $st_strips = $this->comicmanager->db->query($q); //Get all unique strips with category
        $categories = $st_strips->fetchAll(PDO::FETCH_KEY_PAIR);

        $st_missing = $this->comicmanager->db->query("SELECT * FROM $table WHERE $keyfield IS NOT NULL AND category IS NULL");

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
     * @return string[] Output lines
     * @throws exceptions\InvalidMaintenanceTool
     */
    function idToCustomId(): array
    {
        $output = [];

        if ($this->comic->key_field == 'id')
            throw new exceptions\InvalidMaintenanceTool('This tool is only useful for comics using an alternate key field');

        $st_custom_id = $this->comicmanager->db->prepare(sprintf('SELECT DISTINCT id FROM %s WHERE customid=?', $this->comic->id));
        $comic = $this->comic->id;
        $st = $this->comicmanager->db->query("SELECT * FROM $comic WHERE id!=0 AND (customid IS NULL OR id!=customid) ORDER BY id");

        while ($row = $st->fetch())
        {
            $output[] = '<pre>' . print_r($row, true) . '</pre>';
            //$st_custom_id = $comicmanager->get(array('customid'=>$row['id']), true);
            $st_custom_id->execute(array($row['id'])); //Find releases with customid similar to this release id
            $count = $st_custom_id->rowCount();
            if ($count > 1)
            {
                $output[] = sprintf("Multiple ids for customid %d", $row['customid']);
                while ($row_id = $st_custom_id->fetch())
                {
                    $output[] = $row_id[0];
                }
                continue;
            } elseif ($count == 1)
            {
                $release = $st_custom_id->fetch();
                if ($release['id'] != $row['id']) //Check if the matching release has the same id
                {
                    $output[] = sprintf("Release with customid %d has different id: %d", $row['id'], $release['id']);
                    continue;
                } else
                    $output[] = sprintf("Release with customid %d and uid %d has same id as customid %d", $row['customid'], $row['uid'], $row['id']);
            } else
            {
                $output[] = sprintf("Customid %d is free", $row['id']);
            }
            try
            {
                $this->comicmanager->add_or_update(array('customid' => $row['id'], 'uid' => $row['uid']));
            }
            catch (exceptions\comicManagerException $e)
            {
                $output[] = $e->getMessage();
            }
        }

        return $output;
    }
}