<?php


namespace datagutten\comicmanager\elements;


use ArrayAccess;
use Cake\Database;
use datagutten\comicmanager\comicmanager;

abstract class DatabaseObject implements ArrayAccess
{
    /**
     * @var comicmanager
     */
    protected comicmanager $comicmanager;

    public array $fields;

    public function __construct(array $fields)
    {
        foreach ($fields as $field => $value)
        {
            if ($value !== '') //Allow bool false and null but not empty strings
                $this->$field = $value;
        }
    }

    /**
     * Save the object to the database
     * @return ?Database\StatementInterface
     */
    abstract function save(): ?Database\StatementInterface;

    /**
     * Load data from the database to the object
     * @return void
     */
    abstract function load_db(): void;

    public function offsetExists($offset): bool
    {
        return !empty($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }
}