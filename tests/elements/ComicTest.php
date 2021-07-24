<?php

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\tests\Setup;
use InvalidArgumentException;

class ComicTest extends Setup
{
    /**
     * @var Comic
     */
    public Comic $comic;

    public function setUp(): void
    {
        parent::setUp();
        $this->comic = Comic::from_db($this->db, 'pondus');
    }

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
        $this->expectException(InvalidArgumentException::class);
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
        $this->assertEquals(['date', 'site', 'uid', 'customid', 'category', 'id'], $fields);
    }
}
