<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\metadata;
use PHPUnit\Framework\TestCase;

class metadataTest extends TestCase
{
    public function testParsePossibleKeyFields()
    {
        $fields = 'id,customid';
        $fields = metadata::parsePossibleKeyFields($fields);
        $this->assertSame(['id', 'customid'], $fields);
    }

    public function testParsePossibleKeyFieldsSingle()
    {
        $fields = 'id';
        $fields = metadata::parsePossibleKeyFields($fields);
        $this->assertSame(['id'], $fields);
    }

    public function testBuildPossibleKeyFields()
    {
        $fields = ['id', 'customid'];
        $fields = metadata::buildPossibleKeyFields($fields);
        $this->assertSame('id,customid', $fields);
    }

    public function testBuildPossibleKeyFieldsSingle()
    {
        $fields = ['id'];
        $fields = metadata::buildPossibleKeyFields($fields);
        $this->assertSame('id', $fields);
    }

    public function testValidateKeyField()
    {
        foreach (array_keys(metadata::$valid_key_fields) as $field)
        {
            $text = metadata::validateKeyField($field);
            $this->assertSame(metadata::$valid_key_fields[$field], $text);
        }
    }
}
