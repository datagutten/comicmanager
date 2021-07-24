<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\tests\Setup;
use datagutten\tools\files\files;

class ReleaseTest extends Setup
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;

    function setUp(): void
    {
        parent::setUp();
        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
    }

    function testRelease()
    {
        $release = new Release($this->comicmanager, ['id' => 3341, 'category' => 6, 'date' => '20081105', 'site' => 'pondus', 'uid' => 479, 'customid' => 3341]);
        $this->assertEquals(3341, $release->id);
        $this->assertEquals(6, $release->category);
    }

    function testLoadDb()
    {
        $this->comicmanager->add_or_update(['site' => 'pondusadressa', 'date' => '20201009', 'category' => 48, 'id' => 5690, 'customid' => 5690]);
        $release = new Release($this->comicmanager, ['site' => 'pondusadressa', 'date' => '20201009']);
        $this->assertTrue(empty($release->uid));
        $release->load_db();
        $this->assertNotEmpty($release->uid);
    }

    function testLoadDbNotFound()
    {
        $this->expectException(exceptions\ReleaseNotFound::class);
        $release = new Release($this->comicmanager, ['site' => 'pondusadressa', 'date' => '20201009'], false);
        $release->load_db();
    }

    function testNoDate()
    {
        $test_image = files::path_join($this->config['file_path'], 'pondus_blad_digirip', '4623.jpg');
        mkdir(dirname($test_image));
        touch($test_image);

        $this->comicmanager->add_or_update(['site' => 'pondus_blad_digirip', 'id' => 4623, 'customid' => 4623]);
        $release = new Release($this->comicmanager, ['id' => 4623], false);
        $release->load_db();
        $release->image = $release->get_image();
        if (!empty($release->image_error))
            throw $release->image_error;
        $this->assertEquals(4623, $release->id);
    }

    function testHasKey()
    {
        $release = new Release($this->comicmanager, ['site' => 'pondusadressa', 'date' => '20201009'], false);
        $this->assertFalse($release->has_key());
        $this->assertEmpty($release->key());
    }

    function testGetKey()
    {
        $release = new Release($this->comicmanager, ['site' => 'pondusbt', 'id' => 4623, 'customid' => 4623], false);
        $this->assertSame('4623', $release->key());
    }

    function testGetImageInvalidKey()
    {
        //$this->expectException(ImageNotFound::class);
        $release = new Release($this->comicmanager, ['site' => 'pondusbt']);
        $this->assertInstanceOf(exceptions\ImageNotFound::class, $release->image_error);
    }

    function testGetImage()
    {
        $release = new Release($this->comicmanager, ['site' => 'pondusbt', 'image_url' => 'http://test']);
        $this->assertSame('http://test', $release->image->url);
        $file = files::path_join(sys_get_temp_dir(), '4623.jpg');
        touch($file);
        $release = new Release($this->comicmanager, ['site' => 'pondusbt', 'image_file' => $file]);
        $this->assertSame($file, $release->image->file);
    }

    function testFromComics()
    {
        $release_comics = $this->comicmanager->comics->releases_date('pondusadressa', '2021-07-18');
        $release = Release::from_comics($this->comicmanager, $release_comics[0], 'pondusadressa');
        $this->assertStringContainsString('/media/pondusadressa/9/9766c598b5f7b1037e5ade94fc21877b9e07ab2518aabd5ecd0f5cfd1c8961b3.jpg', $release->image_url);
    }

    function testFromComicsInvalidDate()
    {
        $this->expectException(exceptions\comicManagerException::class);
        $this->expectExceptionMessage('Invalid date: 2021-07-32');
        Release::from_comics($this->comicmanager, ['pub_date' => '2021-07-32'], 'pondusadressa');
    }

    function testFromDate()
    {
        $this->comicmanager->add_or_update(['site' => 'pondusadressa', 'date' => '20201009', 'category' => 48, 'id' => 5690, 'customid' => 5690]);
        $release = Release::from_date($this->comicmanager, 'pondusadressa', '20201009');
        $this->assertSame('20201009', $release->date);
        $this->assertSame('pondusadressa', $release->site);
    }

    function testFromInvalidDate()
    {
        $this->expectException(exceptions\comicManagerException::class);
        $this->expectExceptionMessage('Invalid date: 2021-07-32');
        Release::from_date($this->comicmanager, 'pondusadressa', '2021-07-32');
    }
}
