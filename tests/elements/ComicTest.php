<?php

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\tests\Setup;

class ComicTest extends Setup
{
    public function testFrom_db()
    {
        $comic = Comic::from_db($this->db, 'pondus');
        $this->assertEquals('pondus', $comic->id);
        $this->assertFalse(empty($comic->id));
        $this->assertFalse(empty($comic['id']));
    }
}
