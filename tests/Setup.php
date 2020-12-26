<?php


namespace datagutten\comicmanager\tests;


class Setup extends common
{
    function setUp(): void
    {
        parent::setUp();
        $this->config['comics'] = null;
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);

        $setup = new \datagutten\comicmanager\setup(['id' => 'pondus', 'name' => 'Pondus', 'key_field' => 'customid', 'has_categories' => true, 'possible_key_fields' => ['id', 'customid']], $this->config);
        $setup->createComicInfoTable();
        $setup->create();
    }
}