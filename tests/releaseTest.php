<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Release;
use datagutten\tools\files\files;

class releaseTest extends Setup
{
    function testRelease()
    {
        $comicmanager = new comicmanager($this->config);
        $comicmanager->comicinfo('pondus');
        $release = new Release($comicmanager, ['id'=>3341, 'category'=>6, 'date'=>'20081105', 'site'=>'pondus', 'uid'=>479, 'customid'=>3341]);
        $this->assertEquals(3341, $release->id);
        $this->assertEquals(6, $release->category);
    }

    function testLoadDb()
    {
        $comicmanager = new comicmanager($this->config);
        $comicmanager->comicinfo('pondus');
        $comicmanager->add_or_update(['site'=>'pondusadressa', 'date'=>'20201009', 'category'=>48, 'id'=>5690, 'customid'=>5690]);
        $release = new Release($comicmanager, ['site'=>'pondusadressa', 'date'=>'20201009']);
        $this->assertTrue(empty($release->uid));
        $release->load_db();
        $this->assertNotEmpty($release->uid);
    }

    function testNoDate()
    {
        $this->config['comics'] = null;
        $comicmanager = new comicmanager($this->config);
        $test_image = files::path_join($this->config['file_path'], 'pondus_blad_digirip', '4623.jpg');
        if(!file_exists(dirname($test_image)))
            mkdir(dirname($test_image));
        touch($test_image);
        $comicmanager->comicinfo('pondus');
        $comicmanager->add_or_update(['site'=>'pondus_blad_digirip', 'id'=>4623, 'customid'=>4623]);
        $release = new Release($comicmanager, ['id'=>4623], false);
        $release->load_db();
        $release->image = $release->get_image();
        if(!empty($release->image_error))
            throw $release->image_error;
        $this->assertEquals(4623, $release->id);
    }
}
