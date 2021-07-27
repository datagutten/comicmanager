<?php

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\elements\Strip;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\tests\Setup;

class StripTest extends Setup
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;

    public function setUp(): void
    {
        parent::setUp();
        $this->config['comics'] = null;
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);

        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
        $this->comicmanager->releases->release(['id' => 4350, 'site' => 'pondusvg', 'date' => '20190813'])->save();
    }

    public function testFrom_key()
    {
        $comicmanager = new comicmanager($this->config);
        $strip = Strip::from_key('pondus', '4350', 'id', $comicmanager);
        $this->assertInstanceOf(Strip::class, $strip);
        $releases = $strip->releases();
        $this->assertIsArray($releases);
        $this->assertNotEmpty($releases);
        $this->assertInstanceOf(Release::class, $releases[0]);
        $this->assertEquals('4350', $releases[0]->id);
    }

    public function testFromKey()
    {
        $strip = Strip::from_grouping_key($this->comicmanager, '4350', 'id');
        $this->assertInstanceOf(Strip::class, $strip);
        $releases = $strip->releases();
        $this->assertIsArray($releases);
        $this->assertNotEmpty($releases);
        $this->assertInstanceOf(Release::class, $releases[0]);
        $this->assertEquals('4350', $releases[0]->id);
    }

    public function testFromInvalidKey()
    {
        $this->expectException(exceptions\StripNotFound::class);
        $strip = $this->comicmanager->strips->from_key(4, 'id');
        $strip->latest();
    }
}
