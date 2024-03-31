<?php


namespace datagutten\comicmanager\Queries;

use Cake\Database;
use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;

class Comic extends Common
{
    /**
     * Get the name of the category table for a comic
     * @param elements\Comic $comic
     * @return string
     */
    public static function categoryTable(elements\Comic $comic): string
    {
        return $comic->id . '_categories';
    }

    /**
     * Create table for a comic
     * @param elements\Comic $comic
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    public function createTable(elements\Comic $comic): Database\StatementInterface
    {
        $schema = new Database\Schema\TableSchema($comic->id);
        $schema
            ->addColumn('uid', [
                'type' => 'integer',
                'length' => 11,
                'null' => false,
            ])
            ->addColumn('date', [
                'type' => 'string',
                'length' => 8,
                'default' => null
            ])
            ->addColumn('site', [
                'type' => 'string',
                'length' => 100,
                'default' => null,
            ])->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['uid']
            ]);

        return $this->createSchema($schema);
    }

    /**
     * Get all sites for a comic
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function sites(elements\Comic $comic): Database\StatementInterface
    {
        $query = $this->connection->newQuery()
            ->select('site')->distinct('site')->from($comic->id)->order('site');
        return $this->execute($query);
    }

    /**
     * Enable categories for a comic
     * @param elements\Comic $comic Comic object
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function enableCategories(elements\Comic $comic): Database\StatementInterface
    {
        $schema = new Database\Schema\TableSchema(self::categoryTable($comic));
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ])
            ->addColumn('visible', [
                'type' => 'integer',
                'length' => 1,
                'default' => 1,
                'null' => false,
            ])->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);

        $this->createSchema($schema);

        $schema_comic = new Database\Schema\TableSchema($comic->id);
        $schema_comic->addColumn('category', 'integer');
        $sql = $this->addColumnSql($schema_comic, 'category');
        return $this->query($sql);
    }

    /**
     * Get comic categories
     * @param elements\Comic $comic Comic object
     * @param bool $only_visible Show only categories marked as visible
     * @param string|array $fields Fields to fetch
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException Database error
     */
    function categories(elements\Comic $comic, bool $only_visible, string|array $fields): Database\StatementInterface
    {
        $query = $this->connection->selectQuery($fields, self::categoryTable($comic))->order('name');

        if ($only_visible)
            $query = $query->where(['visible' => 1]);

        return $this->execute($query);
    }

    /**
     * Add a category
     * @param elements\Comic $comic
     * @param string $category_name
     * @param bool $visible
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    function addCategory(elements\Comic $comic, string $category_name, bool $visible): Database\StatementInterface
    {
        $query = $this->connection->newQuery()
            ->insert(['name', 'visible'])
            ->values(['name' => $category_name, 'visible' => $visible ? 1 : 0])
            ->into(self::categoryTable($comic));
        return $this->execute($query);
    }

    /**
     * Base query for updating categories
     * @param elements\Comic $comic
     * @param int $id
     * @return Database\Query
     */
    protected function updateCategoryQuery(elements\Comic $comic, int $id): Database\Query
    {
        return $this->connection->newQuery()->update(self::categoryTable($comic))->where(['id' => $id]);
    }

    /**
     * Update category name
     * @param elements\Comic $comic
     * @param int $id
     * @param string $name
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    function updateCategoryName(elements\Comic $comic, int $id, string $name): Database\StatementInterface
    {
        $query = $this->updateCategoryQuery($comic, $id)->set(['name' => $name]);
        return $this->execute($query);
    }

    /**
     * Update category visibility
     * @param elements\Comic $comic Comic object
     * @param int $id Category id
     * @param bool $visible Should the category be visible?
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    function updateCategoryVisibility(elements\Comic $comic, int $id, bool $visible): Database\StatementInterface
    {
        $query = $this->updateCategoryQuery($comic, $id)->set(['visible' => $visible ? 1 : 0]);
        return $this->execute($query);
    }

    /**
     * Delete a category
     * @param elements\Comic $comic Comic object
     * @param int $id Category id
     * @return Database\StatementInterface
     * @throws exceptions\DatabaseException
     */
    function deleteCategory(elements\Comic $comic, int $id): Database\StatementInterface
    {
        $query = $this->connection->newQuery()->delete(self::categoryTable($comic))->where(['id' => $id]);
        return $this->execute($query);
    }
}