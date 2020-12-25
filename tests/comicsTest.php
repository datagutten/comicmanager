<?php

namespace datagutten\comicmanager\tests;


use datagutten\comics_tools\comics_api_client\ComicsAPI;
use datagutten\comics_tools\comics_api_client\exceptions;
use PHPUnit\Framework\TestCase;

class comicsTest extends TestCase
{
    public ComicsAPI $comics;

    public function setUp(): void
    {
        $config = require 'test_config.php';
        $this->comics = new ComicsAPI($config['comics']);
    }

    function testNoReleases()
    {
        $this->expectException(exceptions\NoResultsException::class);
        $this->comics->releases_year('pondus', '2020');
    }

    /*function testReleases()
    {

    }

    function testFormatReleases()
    {

    }*/

}
