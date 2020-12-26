<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\setup;
use InvalidArgumentException;
use PDO;

class setupTest extends common
{
    /**
     * @var setup
     */
    public setup $class;
    public array $comic_data;

    public function setUp(): void
    {
        parent::setUp();
        $this->comic_data = ['id'=>'test_comic', 'name'=>'Test', 'key_field'=>'id', 'has_categories'=>false, 'possible_key_fields'=>['id']];
        $this->class = new setup($this->comic_data, $this->config);
    }

    public function testTable_exists()
    {
        $this->assertFalse($this->class->db_utils->tableExists($this->config['db']['db_name'], 'missing_table'));
    }

    public function testCreateComicInfoTable()
    {
        $this->assertFalse($this->class->db_utils->tableExists($this->config['db']['db_name'], 'comic_info'));
        $this->class->createComicInfoTable();
        $this->assertTrue($this->class->db_utils->tableExists($this->config['db']['db_name'], 'comic_info'));
    }

    public function testHasNotColumn()
    {
        $this->class->createComicInfoTable();
        $this->assertFalse($this->class->db_utils->hasColumn('comic_info', 'bad_column'));
    }
    public function testHasColumn()
    {
        $this->class->createComicInfoTable();
        $result = $this->class->db_utils->hasColumn('comic_info', 'keyfield');
        $this->assertTrue($result);
    }

    public function testAddColumn()
    {
        $this->class->createComicInfoTable();
        $this->class->db_utils->addColumn('comic_info', 'test', 'VARCHAR', 5);
        $this->assertTrue($this->class->db_utils->hasColumn('comic_info', 'test'));
    }

    public function testSetKeyField()
    {
        $this->class->createComicInfoTable();
        $this->class->create();
        $this->class->setKeyField('original_date');
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'original_date'));
    }

    public function testAddKeyField()
    {
        $this->class->createComicInfoTable();
        $this->class->db->query('CREATE TABLE test_comic (`date` int(11) DEFAULT NULL)');
        $this->class->db->query("INSERT INTO comic_info (id,name,keyfield, possible_key_fields) VALUES ('test_comic','Test comic','id', 'id')");
        $this->class->addKeyField('customid');
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'customid'));

        $st_fields = $this->class->db->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('id,customid', $fields);
    }

    public function testInvalidKeyField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key field: foobar');
        $this->class->addKeyField('foobar');
    }

    public function testCreateComic()
    {
        $this->class->createComicInfoTable();
        $this->class->possible_key_fields = ['id', 'customid'];
        $this->class->create();

        $this->assertTrue($this->class->db_utils->tableExists($this->config['db']['db_name'], 'test_comic'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'site'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'id'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'customid'));

        $st_fields = $this->class->db->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('id,customid', $fields);
    }

    public function testCreateComicSingleKey()
    {
        $this->class->createComicInfoTable();
        $this->class->key_field = 'customid';
        $this->class->possible_key_fields = ['customid'];
        $this->class->create();
        $this->assertTrue($this->class->db_utils->tableExists($this->config['db']['db_name'], 'test_comic'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'site'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'customid'));

        $st_fields = $this->class->db->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('customid', $fields);
    }
}
