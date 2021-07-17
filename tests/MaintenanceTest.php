<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\maintenance\Maintenance;
use PHPUnit\Framework\TestCase;

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
        $this->comicmanager->add_or_update(['customid' => 4350, 'site' => 'pondusvg', 'date' => '20190813', 'category' => 1]);
        $this->comicmanager->add_or_update(['customid' => 4350, 'site' => 'pondusadressa', 'date' => '20190813']);
        $this->maintenance = new Maintenance($this->comicmanager);
    }

    public function testPropagateCategories()
    {
        $st = $this->comicmanager->db->prepare('SELECT * FROM pondus WHERE category=1');
        $st->execute();
        $this->assertSame(1, $st->rowCount());
        $this->maintenance->propagateCategories();
        $st->execute();
        $this->assertSame(2, $st->rowCount());
    }
}
