<?php


namespace datagutten\comicmanager\tests;


use datagutten\comics_tools\comics_api_client\ComicsAPICache;

class Setup extends common
{
    function setUp(): void
    {
        parent::setUp();
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);
        if (empty($this->config['comics']['secret_key']))
            $this->config['comics'] = null;

        $setup = new \datagutten\comicmanager\setup(['id' => 'pondus', 'name' => 'Pondus', 'key_field' => 'customid', 'has_categories' => true, 'possible_key_fields' => ['id', 'customid']], $this->config);
        $setup->createComicInfoTable();
        $setup->create();
        ComicsAPICache::create_table($setup->db);
    }
}