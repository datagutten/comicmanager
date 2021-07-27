<?php


namespace datagutten\comicmanager\tests;


use datagutten\comicmanager\elements;
use datagutten\comics_tools\comics_api_client\ComicsAPICache;

class Setup extends common
{
    public elements\Comic $comic;

    function setUp(): void
    {
        parent::setUp();
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);
        if (empty($this->config['comics']['secret_key']))
            $this->config['comics'] = null;


        $this->comic = new elements\Comic($this->config['db'], [
            'id' => 'pondus',
            'name' => 'Pondus',
            'key_field' => 'customid',
            'has_categories' => true,
            'possible_key_fields' => ['id', 'customid']]);
        $this->comic->create();
        ComicsAPICache::create_table($this->db);
    }
}