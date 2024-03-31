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
}
