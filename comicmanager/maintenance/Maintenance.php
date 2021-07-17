<?php


namespace datagutten\comicmanager\maintenance;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions\comicManagerException;
use PDO;

class Maintenance
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;

    function __construct(comicmanager $comicmanager)
    {
        $this->comicmanager = $comicmanager;
    }

    /**
     * Propagate category to all releases of a strip
     * @throws comicManagerException
     */
    function propagateCategories(): array
    {
        if (!$this->comicmanager->info->has_categories)
            throw new comicManagerException(sprintf('Comic do not have categories'));
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
                $this->comicmanager->add_or_update(['uid' => $strip['uid'], 'category' => $categories[$strip[$keyfield]]]);
                $output[] = sprintf('Set category to %d for uid %d', $categories[$strip[$keyfield]], $strip['uid']);
            }
        }
        return $output;
    }
}