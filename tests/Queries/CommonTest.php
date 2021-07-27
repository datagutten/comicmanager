<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\comicmanager\tests\Queries;

use Cake\Database;
use datagutten\comicmanager\Queries\Common;
use datagutten\comicmanager\elements;

class CommonTest extends \datagutten\comicmanager\tests\common
{
    /**
     * @var Common
     */
    protected Common $queries;

    public function setUp(): void
    {
        parent::setUp();
        $this->queries = new Common($this->config['db']);
    }

    /*    public function testColumns()
        {

        }

        public function testHasColumn()
        {

        }*/

    public function testTableExists()
    {
        $this->assertFalse($this->queries->tableExists('test'));
        $schema = new Database\Schema\TableSchema('test');
        $schema
            ->addColumn('id', [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);

        $this->queries->createSchema($schema);
        $this->assertTrue($this->queries->tableExists('test'));
    }

    public function testCompareFields()
    {
        $obj = new elements\Comic($this->config['db'], ['name'=>'test', 'key_field'=>'customid']);
        $fields = $this->queries->compareFields(['name'=>'test2', 'key_field'=>'customid'], $obj);
        $this->assertEquals(['name'=>'test'], $fields);
    }
}
