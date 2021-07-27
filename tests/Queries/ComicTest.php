<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests\Queries;


use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\Queries;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\tests\common;
use PDO;

class ComicTest extends common
{
    public array $comic_data;
    /**
     * @var Queries\Comic
     */
    private Queries\Comic $queries_comic;
    private elements\Comic $comic;

    public function setUp(): void
    {
        parent::setUp();
        /*        $this->comic_data = ['id'=>'test_comic', 'name'=>'Test', 'key_field'=>'id', 'has_categories'=>false, 'possible_key_fields'=>['id']];
                $this->class = new setup($this->comic_data, $this->config);
                $this->queries_comic_meta = new queries\ComicMetadata($this->config['db']);*/
        $this->queries_comic = new Queries\Comic($this->config['db']);
        $this->comic = new elements\Comic(
            $this->config['db'], [
            'id' => 'test_comic',
            'name' => 'Test comic',
            'key_field' => 'id',
            'has_categories' => false,
            'possible_key_fields' => [
                'id',
                'customid'
            ]
        ]);
    }

    public function testCreateTable()
    {
        $this->queries_comic->createTable($this->comic);
        $this->assertTrue($this->queries_comic->tableExists('test_comic'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'uid'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'site'));
    }

    public function testCreateComicMetadata()
    {
        $this->assertFalse($this->queries_comic->tableExists('comic_info'));
        $this->queries_comic_meta->createMetadataTable();
        $this->assertTrue($this->queries_comic->tableExists('comic_info'));

        $comic = new Comic($this->config['db'], ['id'=>'test_comic', 'name'=>'Test comic', 'key_field'=>'id', 'has_categories'=>false, 'possible_key_fields'=>['id']]);
        $this->queries_comic_meta->insert($comic);
        $st = $this->queries_comic_meta->info(new Comic($this->config['db'], ['id'=>'test_comic']));
        $this->assertEquals(1, $st->rowCount());
    }

    public function testCreateComic()
    {
        $comic = new Comic(
            $this->config['db'], [
            'id' => 'test_comic',
            'name' => 'Test comic',
            'key_field' => 'id',
            'has_categories' => false,
            'possible_key_fields' => [
                'id',
                'customid'
            ]
        ]);
        $comic->create();

        $this->assertTrue($this->queries_comic->tableExists('test_comic'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'site'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'id'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'customid'));

        $st_fields = $this->queries_comic->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('id,customid', $fields);
    }

    public function testCreateComicSingleKey()
    {
        $comic = new Comic(
            $this->config['db'], [
            'id' => 'test_comic',
            'name' => 'Test comic',
            'key_field' => 'customid',
            'has_categories' => false,
            'possible_key_fields' => ['customid']
        ]);

        $comic->create();
        $this->assertTrue($this->queries_comic->tableExists('test_comic'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'date'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'site'));
        $this->assertTrue($this->queries_comic->hasColumn('test_comic', 'customid'));

        $st_fields = $this->queries_comic->query('SELECT possible_key_fields FROM comic_info WHERE id=\'test_comic\'');
        $fields = $st_fields->fetch(PDO::FETCH_COLUMN);
        $this->assertSame('customid', $fields);
    }

    public function testAddInvalidKeyField()
    {
        $this->expectException(exceptions\ComicInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key field: foobar');
        $this->comic->addKeyField('foobar');
    }

    public function testEnableCategories()
    {
        $this->comic->create();
        $this->assertFalse($this->queries_comic->tableExists($this->comic->id.'_categories'));
        $this->comic->enableCategories();
        $this->assertTrue($this->queries_comic->tableExists($this->comic->id.'_categories'));
        $this->assertTrue($this->queries_comic->hasColumn($this->comic->id, 'category'));
        $this->comic->load_db();
        $this->assertTrue($this->comic->has_categories);
    }

    public function testCharset()
    {
        $this->comic->name = 'test æøå';
        $this->comic->create();
        $this->comic->load_db();
        $this->assertEquals('UTF-8', mb_detect_encoding($this->comic->name));
        $this->assertEquals('test æøå', $this->comic->name);
    }
}
