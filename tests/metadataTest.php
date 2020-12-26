<?php

namespace datagutten\comicmanager\tests;

use datagutten\comicmanager\elements\Comic;
use PHPUnit\Framework\TestCase;

class metadataTest extends TestCase
{
    public function testParsePossibleKeyFields()
    {
        $fields = 'id,customid';
        $fields = Comic::parsePossibleKeyFields($fields);
        $this->assertSame(['id', 'customid'], $fields);
    }

    public function testParsePossibleKeyFieldsSingle()
    {
        $fields = 'id';
        $fields = Comic::parsePossibleKeyFields($fields);
        $this->assertSame(['id'], $fields);
    }

    public function testBuildPossibleKeyFields()
    {
        $fields = ['id', 'customid'];
        $fields = Comic::buildPossibleKeyFields($fields);
        $this->assertSame('id,customid', $fields);
    }

    public function testBuildPossibleKeyFieldsSingle()
    {
        $fields = ['id'];
        $fields = Comic::buildPossibleKeyFields($fields);
        $this->assertSame('id', $fields);
    }

    public function testValidateKeyField()
    {
        foreach (array_keys(Comic::$key_fields) as $field)
        {
            $text = Comic::validKeyField($field);
            $this->assertSame(Comic::$key_fields[$field], $text);
        }
    }
}
