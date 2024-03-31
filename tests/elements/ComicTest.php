<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\Queries;
use datagutten\comicmanager\tests\Setup;

class ComicTest extends Setup
{
    public function testFrom_db()
    {
        $this->assertEquals('pondus', $this->comic->id);
        $this->assertFalse(empty($this->comic->id));
        $this->assertFalse(empty($this->comic['id']));
    }

    public function testAllowedKeyField()
    {
        $allowed = $this->comic->allowedKeyField('customid');
        $this->assertSame('Custom grouping id', $allowed);
    }

    public function testNotAllowedKeyField()
    {
        $this->expectException(exceptions\ComicInvalidArgumentException::class);
        $this->expectExceptionMessage('original_date is not a valid key field for pondus');
        $this->comic->allowedKeyField('original_date');
    }

    public function testSetKeyField()
    {
        $this->assertSame('customid', $this->comic->key_field);
        $this->comic->setKeyField('id');
        $this->assertSame('id', $this->comic->key_field);
    }

    public function testArraySetInvalidKeyField()
    {
        $this->expectExceptionMessage('Invalid key field: foo');
        $this->comic['key_field'] = 'foo';
    }

    public function testArraySetInvalidId()
    {
        $this->expectExceptionMessage('Invalid comic id: foo+');
        $this->comic['id'] = 'foo+';
    }

    public function testUnset()
    {
        $this->assertSame('customid', $this->comic->key_field);
        unset($this->comic['key_field']);
        $this->assertFalse(isset($this->comic->key_field));
    }

    public function testFields()
    {
        $fields = $this->comic->fields;
        $this->assertEquals(['uid', 'date', 'site', 'id', 'customid', 'category'], $fields);
    }

    public function testAddKeyField()
    {
        $this->comic->addKeyField('original_date');
        $this->comic->allowedKeyField('original_date');
        $this->comic->load_db();
        $this->assertEquals(['id', 'customid' ,'original_date'], $this->comic->possible_key_fields);
        $this->assertEquals('customid', $this->comic->key_field, 'Primary key field should not be changed');
    }

    public function testAddInvalidKeyField()
    {
        $this->expectException(exceptions\ComicInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key field: foobar');
        $this->comic->addKeyField('foobar');
    }

    public function testUpdate()
    {
        $this->assertEquals('Pondus', $this->comic->name);
        $this->comic->name = 'Pondus test';
        $this->comic->save();
        $this->comic->load_db();
        $this->assertEquals('Pondus test', $this->comic->name);
    }

    public function testCategoriesInvalidComic()
    {
        $comic = new elements\Comic(
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
        $this->expectExceptionMessage('Comic does not have categories');
        $comic->categories();
    }

    public function testAddCategory()
    {
        $this->assertEmpty($this->comic->categories());
        $this->comic->addCategory('test category');
        $this->assertContains('test category', $this->comic->categories());

        $this->comic->addCategory('test hidden', false);
        $this->assertContains('test hidden', $this->comic->categories());
        $this->assertNotContains('test hidden', $this->comic->categories(true));
    }

    public function testDeleteCategory()
    {
        $this->assertEmpty($this->comic->categories());
        $this->comic->addCategory('delete me');
        $this->assertContains('delete me', $this->comic->categories());
        $this->comic->deleteCategory(1);
        $this->assertNotContains('delete me', $this->comic->categories());
    }

    public function testRenameCategory()
    {
        $queries_comic = new Queries\Comic($this->config['db']);
        $this->assertNotContains('renamed category', $this->comic->categories());
        $this->comic->addCategory('rename me');
        $queries_comic->updateCategoryName($this->comic, 1, 'renamed category');
        $this->assertContains('renamed category', $this->comic->categories());
    }

    public function testHideCategory()
    {
        $queries_comic = new Queries\Comic($this->config['db']);
        $this->comic->addCategory('test hidden');
        $queries_comic->updateCategoryVisibility($this->comic, 1, false);
        $this->assertContains('test hidden', $this->comic->categories());
        $this->assertNotContains('test hidden', $this->comic->categories(true));
    }
}
