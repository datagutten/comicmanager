<?php

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\release;
use datagutten\comicmanager\Strip;
use datagutten\comicmanager\tests\Setup;

class stripTest extends Setup
{
    public function setUp(): void
    {
        parent::setUp();
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);

        $comicmanager = new comicmanager($this->config);
        $comicmanager->comicinfo('pondus');
        $comicmanager->add_or_update(['id' => 4350, 'site' => 'pondusvg', 'date' => '20190813']);
    }

    public function testFrom_key()
    {
        $comicmanager = new comicmanager($this->config);
        $strip = Strip::from_key('pondus', '4350', 'id', $comicmanager);
        $this->assertInstanceOf(Strip::class, $strip);
        $releases = $strip->releases();
        $this->assertIsArray($releases);
        $this->assertNotEmpty($releases);
        $this->assertInstanceOf(release::class, $releases[0]);
        $this->assertEquals('4350', $releases[0]->id);
    }
}
