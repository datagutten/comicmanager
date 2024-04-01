<?php

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Releases;
use datagutten\comicmanager\tests\Setup;

class ReleasesTest extends Setup
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    /**
     * @var Releases
     */
    private Releases $releases;

    function setUp(): void
    {
        parent::setUp();
        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
        $this->releases = new Releases($this->comicmanager);
    }

    public function testCategory()
    {
        $this->comicmanager->releases->save(['customid' => 4350, 'id' => 4350, 'site' => 'pondus', 'date' => '20140424', 'category' => 1]);
        $this->comicmanager->releases->save(['customid' => 4350, 'id' => 4350, 'site' => 'pondusvg', 'date' => '20190813', 'category' => 1]);
        $this->comicmanager->releases->save(['customid' => 4351, 'id' => 4351, 'site' => 'pondusadressa', 'date' => '20190814', 'category' => 2]);
        $this->comicmanager->releases->save(['customid' => 4352, 'id' => 4352, 'site' => 'pondusadressa', 'date' => '20190815', 'category' => 2]);
        $strips = $this->releases->category(1);
        $this->assertCount(1, $strips);
        $this->assertEquals('20190813', $strips[0]->date);

        $strips = $this->releases->category(2);
        $this->assertCount(2, $strips);
    }

    public function testWildcard()
    {
        $this->comicmanager->releases->save(['customid' => 4350, 'id' => 4350, 'site' => 'pondus', 'date' => '20140424']);
        $this->comicmanager->releases->save(['customid' => 4350, 'id' => 4350, 'site' => 'pondusvg', 'date' => '20190813']);
        $this->comicmanager->releases->save(['customid' => 4351, 'id' => 4351, 'site' => 'pondusadressa', 'date' => '20190814']);
        $this->comicmanager->releases->save(['customid' => 4352, 'id' => 4352, 'site' => 'pondusadressa', 'date' => '20190815']);
        $releases = $this->comicmanager->releases->wildcard('pondusadressa', '201908%');
        $this->assertCount(2, $releases);
        $this->assertEquals('20190814', $releases[0]->date);

        $releases = $this->comicmanager->releases->wildcard('pondus%', '201908%');
        $this->assertCount(3, $releases);
        $this->assertEquals('20190813', $releases[0]->date);
        $this->assertEquals('pondusvg', $releases[0]->site);

        $releases = $this->comicmanager->releases->wildcard('pondus%', '20190813');
        $this->assertCount(1, $releases);
        $this->assertEquals('20190813', $releases[0]->date);
        $this->assertEquals('pondusvg', $releases[0]->site);
    }
}
