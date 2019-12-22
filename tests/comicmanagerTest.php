<?php

namespace datagutten\comicmanager\tests;

use comicmanager;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class comicmanagerTest extends TestCase
{
    public function testCategoriesArray()
    {
        $comics = new comicmanager();
        $comics->comicinfo('hjalmar');
        $categories = $comics->categories(true);
        $this->assertIsArray($categories);
    }
    public function testCategoriesObject()
    {
        $comics = new comicmanager();
        $comics->comicinfo('hjalmar');
        $categories = $comics->categories(true, true);
        $this->assertInstanceOf(PDOStatement::class, $categories);
    }
}
