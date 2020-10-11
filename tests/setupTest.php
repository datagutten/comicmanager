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
    public $class;

    public function setUp(): void
    {
        parent::setUp();
        $this->class = new setup($this->config);
    }

    public function testTable_exists()
    {
        $this->assertFalse($this->class->tableExists('missing_table'));
    }

    public function testCreateComicInfoTable()
    {
        $this->assertFalse($this->class->tableExists('comic_info'));
        $this->class->createComicInfoTable();
        $this->assertTrue($this->class->tableExists('comic_info'));
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
        $this->class->createComic('test_comic', 'test', 'id', false, ['id']);
        $this->class->setKeyField('test_comic', 'original_date');
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'original_date'));
    }

    public function testAddKeyField()
    {
        $this->class->createComicInfoTable();
        $this->class->db->query('CREATE TABLE test_comic (`date` int(11) DEFAULT NULL)');
        $this->class->db->query("INSERT INTO comic_info (id,name,keyfield, possible_key_fields) VALUES ('test_comic','Test comic','id', 'id')");
        $this->class->addKeyField('test_comic', 'customid');
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'customid'));

        $st_fields = $this->class->db->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('id,customid', $fields);
    }

    public function testInvalidKeyField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key field: foobar');
        $this->class->addKeyField('comic_test', 'foobar');
    }

    public function testCreateComic()
    {
        $this->class->createComicInfoTable();
        $this->class->createComic('test_comic', 'Test', 'customid', true, ['id', 'customid']);
        $this->assertTrue($this->class->tableExists('test_comic'));
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
        $this->class->createComic('test_comic', 'Test', 'customid', true);
        $this->assertTrue($this->class->tableExists('test_comic'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'site'));
        $this->assertTrue($this->class->db_utils->hasColumn('test_comic', 'customid'));

        $st_fields = $this->class->db->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('customid', $fields);
    }
}
