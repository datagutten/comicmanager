<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests;

use Cake\Database;
use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Comic;

class comicmanagerTest extends Setup
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;

    public function setUp(): void
    {
        parent::setUp();
        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
    }

    public function testComicInfo()
    {
        $info = $this->comicmanager->comicinfo('pondus');
        $this->assertInstanceOf(Comic::class, $info);
        $this->assertSame('Pondus', $info['name']);
        $this->assertSame('customid', $info['keyfield']);
    }

    public function testCategoriesArray()
    {
        $categories = $this->comicmanager->info->categories(true);
        $this->assertIsArray($categories);
    }

    public function testCategoriesObject()
    {
        $categories = $this->comicmanager->info->categories(true, true);
        $this->assertInstanceOf(Database\StatementInterface::class, $categories);
    }

    public function testComicList()
    {
        $comics = $this->comicmanager->comic_list();
        $this->assertIsArray($comics);
    }

    public function testSites()
    {
        $this->comicmanager->releases->save(['site' => 'pondusadressa', 'date' => '20201009', 'category' => 48, 'id' => 5690, 'customid' => 5690]);
        $sites = $this->comicmanager->info->sites();
        $this->assertContains('pondusadressa', $sites);
    }
}
