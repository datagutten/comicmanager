<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\maintenance\Maintenance;

class MaintenanceTest extends Setup
{
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    /**
     * @var Maintenance
     */
    public Maintenance $maintenance;

    public function setUp(): void
    {
        parent::setUp();
        $this->config['comics'] = null;
        if (!file_exists($this->config['file_path']))
            mkdir($this->config['file_path']);

        $this->comicmanager = new comicmanager($this->config);
        $this->comicmanager->comicinfo('pondus');
        $this->comicmanager->releases->release(['customid' => 4350, 'id' => 4350, 'site' => 'pondusvg', 'date' => '20190813', 'category' => 1], false)->save();
        $this->comicmanager->releases->release(['customid' => 4350, 'id' => 4350, 'site' => 'pondusadressa', 'date' => '20190813'], false)->save();
        $this->maintenance = new Maintenance($this->comicmanager);
    }

    public function testPropagateCategories()
    {
        $st = $this->comicmanager->db->query('SELECT * FROM pondus WHERE category=1');
        $this->assertSame(1, $st->rowCount());
        $this->maintenance->propagateCategories();
        $st->execute();
        $this->assertSame(2, $st->rowCount());
    }

    public function testIdToCustomId()
    {
        $this->comicmanager->releases->release(['id' => 4310, 'site' => 'pondusadressa', 'date' => '20190815'], false)->save();
        $release = $this->comicmanager->releases->release(['id' => 4310], false);
        $release->load_db();
        $this->assertEmpty($release['customid']);
        $output = $this->maintenance->idToCustomId();
        //$release = $this->comicmanager->get(['id'=>4310]);
        $release->load_db();
        $this->assertEquals(4310, $release['customid']);
        $this->assertContains('Customid 4310 is free', $output);
    }

    /**
     * Two strips with the same customid has different id
     * @throws exceptions\InvalidMaintenanceTool
     * @throws exceptions\comicManagerException
     */
    public function testIdToCustomIdDifferentId()
    {
        $release_not_updatable = $this->comicmanager->releases->release(['id' => 4310, 'site' => 'pondusadressa', 'date' => '20190815'], false);
        $release_not_updatable->save();
        $release_updatable = $this->comicmanager->releases->release(['id' => 4311, 'site' => 'pondustest', 'date' => '20190815', 'customid' => 4310], false);
        $release_updatable->save();

        $output = $this->maintenance->idToCustomId();

        $release_updatable->load_db();
        $release_not_updatable->load_db();

        $this->assertEquals(4311, $release_updatable->customid);
        $this->assertEmpty($release_not_updatable->customid);
        $this->assertEquals(['Release with customid 4310 has different id: 4311', 'Customid 4311 is free'], $output);
    }

    public function testIdToCustomIdMultipleId()
    {
        $release_not_updatable = $this->comicmanager->releases->release(['id' => 4310, 'site' => 'pondusadressa', 'date' => '20190813', 'customid' => 4310], false);
        $release_not_updatable->save();
        $release_updatable = $this->comicmanager->releases->release(['id' => 4311, 'site' => 'pondustest', 'date' => '20190814', 'customid' => 4310], false);
        $release_updatable->save();
        $this->comicmanager->releases->release(['id' => 4311, 'site' => 'pondustest', 'date' => '20190813', 'customid' => 4311], false)->save();

        $output = $this->maintenance->idToCustomId();

        $release_updatable->load_db();
        $release_not_updatable->load_db();

        //$this->assertEquals(4310, $release_updatable->customid);
        //$this->assertEmpty($release_not_updatable->customid);
        //$this->assertEquals(['Multiple ids for customid 4310', '4310', '4311'], $output);
        $this->assertEquals('Release with customid 4310 and uid 3 has same id as customid 4311', $output[0]);
    }
}
