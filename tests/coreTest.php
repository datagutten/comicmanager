<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\core;

class coreTest extends common
{
    public function testClean_value()
    {
        $bad_value = 'test\\_string';
        $clean_value = core::clean_value($bad_value);
        $this->assertSame('test_string', $clean_value);
    }
}
