<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests\elements;

use Cake\Database;
use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements;
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
        $this->comicmanager->releases->save(['site' => 'pondusadressa', 'date' => '20201009', 'category' => 48, 'id' => 5690, 'customid' => 5690]);
        $release = new Release($this->comicmanager, ['site' => 'pondusadressa', 'date' => '20201009']);
        $this->assertTrue(empty($release->uid));
        $release->load_db();
        $this->assertNotEmpty($release->uid);
    }

    function testInsert()
    {
        $release = new Release($this->comicmanager, ['id' => 3341, 'category' => 6, 'date' => '20081105', 'site' => 'pondus', 'customid' => 3341]);
        $this->assertFalse(isset($release->uid));
        $st = $release->save();
        $this->assertEquals(1, $st->rowCount());
        $release2 = new Release($this->comicmanager, ['id' => 3341], false);
        $release2->load_db();
        $this->assertNotEmpty($release2->uid);
        $this->assertEquals('20081105', $release2->date);
    }

    function testUpdate()
    {
        $release = new Release($this->comicmanager, ['id' => 3341, 'category' => 6, 'date' => '20081105', 'site' => 'pondus', 'customid' => 3341]);
        $release->save();
        $release->category = 7;
        $release->save();

        $release2 = new Release($this->comicmanager, ['id' => 3341], false);
        $release2->load_db();
        $this->assertEquals(7, $release2->category);
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

        $this->comicmanager->releases->save(['site' => 'pondus_blad_digirip', 'id' => 4623, 'customid' => 4623]);
        $release = new Release($this->comicmanager, ['id' => 4623], false);
        $release->load_db();
        $release->image = $release->get_image($this->comicmanager);
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

    function testHasKey2()
    {
        $setup = new elements\Comic($this->config['db'], [
            'id' => 'hjalmar',
            'name' => 'Hjalmar',
            'key_field' => 'id',
            'has_categories' => true,
            'possible_key_fields' => ['id']]);
        $setup->create();

        $this->comicmanager->comicinfo('hjalmar');
        $release = new Release($this->comicmanager, ['date' => '20130327', 'site' => 'hjalmar', 'id' => 574, 'category' => 1]);
        $release->save();
        $this->assertTrue($release->has_key());
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
        if (empty($this->comicmanager->comics))
            $this->markTestSkipped('Comics not initialized');

        $release_comics = $this->comicmanager->comics->releases_date('pondusadressa', '2021-07-18');
        $release = Release::from_comics($this->comicmanager, $release_comics[0], 'pondusadressa');
        $this->assertStringContainsString('/media/pondusadressa/9/9766c598b5f7b1037e5ade94fc21877b9e07ab2518aabd5ecd0f5cfd1c8961b3.jpg', $release->image_url);
    }

    function testFromComicsInvalidDate()
    {
        if (empty($this->comicmanager->comics))
            $this->markTestSkipped('Comics not initialized');

        $this->expectException(exceptions\comicManagerException::class);
        $this->expectExceptionMessage('Invalid date: 2021-07-32');
        Release::from_comics($this->comicmanager, ['pub_date' => '2021-07-32'], 'pondusadressa');
    }

    function testFromDate()
    {
        $this->comicmanager->releases->save(['site' => 'pondusadressa', 'date' => '20201009', 'category' => 48, 'id' => 5690, 'customid' => 5690]);
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

    function testUpdateNoChanges()
    {
        $release = new Release($this->comicmanager, ['id' => 3341, 'category' => 6, 'date' => '20081105', 'site' => 'pondus', 'customid' => 3341]);
        $this->assertInstanceOf(Database\StatementInterface::class, $release->save());
        $this->assertEmpty($release->save());
    }

    function testUpdateNoUid()
    {
        $release = new Release($this->comicmanager, ['id' => 3341, 'category' => 6, 'date' => '20081105', 'site' => 'pondus', 'customid' => 3341]);
        $release->save();

        $release2 = new Release($this->comicmanager, ['id' => 3341, 'category' => 7, 'date' => '20081105', 'site' => 'pondus', 'customid' => 3341]);
        $release2->save(false);
        $release2->load_db();
        $this->assertEquals(7, $release2->category);
    }
}
