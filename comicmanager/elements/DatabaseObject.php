<?php


namespace datagutten\comicmanager\elements;


use ArrayAccess;
use Cake\Database;
use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\Queries;

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
            if (!empty($field))
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
     * @return mixed
     */
    abstract function load_db();

    public function offsetExists($offset): bool
    {
        return !empty($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}