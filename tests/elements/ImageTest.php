<?php /** @noinspection ALL */

namespace datagutten\comicmanager\tests\elements;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\elements\Image;
use datagutten\comicmanager\exceptions\ImageNotFound;
use datagutten\comicmanager\tests\Setup;
use datagutten\tools\files\files;
use Symfony\Component\Filesystem\Filesystem;

class ImageTest extends Setup
{
    /**
     * @var comicmanager
     */
    protected comicmanager $comicmanager;
    private string $file;

    public function setUp(): void
    {
        parent::setUp();
        $filesystem = new Filesystem();
        $filesystem->remove($this->config['file_path']);
        $filesystem->mkdir(files::path_join($this->config['file_path'], 'pondus'));

        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
        $this->comicmanager->releases->release(['id' => 4350, 'site' => 'pondusvg', 'date' => '20190813'], false)->save();
    }

    public function testFromRandomFile()
    {
        $file = files::path_join(sys_get_temp_dir(), '4623.jpg');
        touch($file);
        $image = Image::from_file($file);
        $this->assertSame(Image::file_proxy($file), $image->url);
    }

    public function testFromFile()
    {
        $file = files::path_join($this->comicmanager->files->file_path, 'pondus', '201703', '20170331.gif');
        if (!file_exists($file))
        {
            mkdir(dirname($file), 0777, true);
            touch($file);
        }
        $image = Image::from_file($file, $this->comicmanager);
        $this->assertSame($this->comicmanager->web_image_root . '/pondus/201703/20170331.gif', $image->url);
    }

    public function testFromMissingFile()
    {
        $file = files::path_join(__DIR__, 'pondus', '201703', '20170331.gif');
        $this->expectException(ImageNotFound::class);
        $this->expectExceptionMessageMatches('/Image file .+ not found/');
        Image::from_file($file);
    }

    public function testFrom_url()
    {
        $url = 'http://comics.test.local/media/pondusbt/5/58f4787f06c3e7381ecb7ca2e204d71856edcac75fb73356b95f8e190c452e41.gif';
        $this->assertSame($url, Image::from_url($url)->url);
    }

    public function testFromKey()
    {
        $file = files::path_join($this->comicmanager->files->file_path, 'pondus', '4321.jpg');
        touch($file);

        $image = Image::from_key('pondus', 4321, 'id', $this->comicmanager);
        $this->assertSame($file, $image->file);
    }

    public function testFromCustomKey()
    {
        $file = files::path_join($this->comicmanager->files->file_path, 'pondus', 'custom_4321.jpg');
        touch($file);

        $image = Image::from_key('pondus', 4321, 'customid', $this->comicmanager);
        $this->assertSame($file, $image->file);
    }

    public function testFromDateFile()
    {
        $file = files::path_join($this->comicmanager->files->file_path, 'pondusvg', '201908', '20190813.jpg');
        mkdir(files::path_join($this->comicmanager->files->file_path, 'pondusvg', '201908'), 0777, true);
        touch($file);
        $image = Image::from_date('pondusvg', '20190813', $this->comicmanager);
        $this->assertSame($this->comicmanager->web_image_root . '/pondusvg/201908/20190813.jpg', $image->url);
    }

    public function testFromComicsDate()
    {
        $this->assertNotEmpty($this->comicmanager->comics, 'Comics not set, missing environment variables?');
        $image = Image::from_date('pondusbt', '20150519 ', $this->comicmanager);
        $this->assertStringContainsString('/media/pondusbt/5/58f4787f06c3e7381ecb7ca2e204d71856edcac75fb73356b95f8e190c452e41.gif', $image->url);
    }

    public function testFromInvalidDate()
    {
        $this->expectException(ImageNotFound::class);
        Image::from_date('pondusbt', '20150532 ', $this->comicmanager);
    }
}
