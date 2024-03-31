<?php


namespace datagutten\comicmanager\Queries;

use Cake\Database;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;

class ComicMetadata extends Common
{
    /**
     * Get valid fields for the comic
     * @param elements\Comic $comic Comic object
     * @return array Field names
     * @throws exceptions\DatabaseException Database error
     */
    public function fields(elements\Comic $comic): array
    {
        return $this->columns($comic->id);
    }

    /**
     * Get comic info
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function info(elements\Comic $comic): Database\StatementInterface
    {
        $query = $this->connection->selectQuery('*', 'comic_info')->where(['id' => $comic->id]);
        return $this->execute($query);
    }

    /**
     * Get values for the database fields
     * @param elements\Comic $comic Comic object
     * @return array
     */
    protected function values_to_db(elements\Comic $comic): array
    {
        return [
            'possible_key_fields' => elements\Comic::buildPossibleKeyFields($comic->possible_key_fields),
            'has_categories' => $comic->has_categories ? 1 : 0,
            'id' => $comic->id,
            'name' => $comic->name,
            'key_field' => $comic->key_field,
        ];
    }

    /**
     * Insert metadata record
     * @param elements\Comic $comic Comic object
     * @throws exceptions\DatabaseException Database error
     */
    public function insert(elements\Comic $comic)
    {
        $fields = $this->values_to_db($comic);
        $query = $this->connection->insertQuery('comic_info')->insert(array_keys($fields))->values($fields);
        $this->execute($query);
    }

    /**
     * Update metadata record with changed fields
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    public function update(elements\Comic $comic): Database\StatementInterface
    {
        $fields = $this->values_to_db($comic);
        $query = $this->connection->updateQuery('comic_info', conditions: ['id' => $comic->id]);
        $in_db = $this->connection->selectQuery('*', 'comic_info')
            ->where(['id' => $comic->id])
            ->execute()
            ->fetch('assoc');

        if ($comic->possible_key_fields != elements\Comic::parsePossibleKeyFields($in_db['possible_key_fields']))
            $query->set('possible_key_fields', elements\Comic::buildPossibleKeyFields($comic->possible_key_fields));

        if ($comic->has_categories != ($in_db['has_categories'] == 1))
            $query->set('has_categories', $comic->has_categories ? 1 : 0);

        foreach (array_diff($fields, $in_db) as $key => $value)
        {
            $query->set($key, $value);
        }
        return $this->execute($query);
    }

    /**
     * Add a key field to the comic
     * @param elements\Comic $comic Comic object
     * @param string $key_field Key field
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     * @throws exceptions\ComicInvalidArgumentException Key field is already added
     */
    public function addKeyField(elements\Comic $comic, string $key_field): Database\StatementInterface
    {
        if ($this->hasColumn($comic->id, $key_field))
            throw new exceptions\ComicInvalidArgumentException(sprintf('%s is already added as key field for %s', $key_field, $comic->id));

        $schema = new Database\Schema\TableSchema($comic->id);
        $lengths = ['id' => 5, 'customid' => 5, 'original_date' => 11];
        $schema->addColumn($key_field, ['type' => 'integer', 'length' => $lengths[$key_field]]);
        return $this->query($this->addColumnSql($schema, $key_field));
    }

    /**
     * Create comic metadata table
     * @throws exceptions\DatabaseException Database error
     */
    function createMetadataTable(): Database\StatementInterface
    {
        $schema = new Database\Schema\TableSchema('comic_info');
        $schema
            ->addColumn('id', [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ])
            ->addColumn('name', [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ])
            ->addColumn('key_field', [
                'type' => 'string',
                'length' => 15,
                'null' => false,
            ])
            ->addColumn('has_categories', [
                'type' => 'integer',
                'length' => 1,
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('possible_key_fields', [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);
        $sql = $schema->createSql($this->connection);
        return $this->query($sql[0]);
    }
}