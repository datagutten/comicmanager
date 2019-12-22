<?php


namespace datagutten\comicmanager;


use InvalidArgumentException;

class metadata extends core
{
    /**
     * @var array Valid key fields
     */
    public static $valid_key_fields = ['id' => 'ID (printed on strip)', 'customid' => 'Custom grouping id', 'original_date' => 'Original published date'];

    /**
     * Parse a comma separated string with possible key fields
     * @param string $fields Comma separated string with field names
     * @return array Fields
     */
    public static function parsePossibleKeyFields($fields)
    {
        if(strpos($fields,',')!==false)
            return explode(',', $fields);
        else
            return [$fields];
    }

    /**
     * Build a comma separated string with possible key fields
     * @param array $fields
     * @return string Comma separated string with field names
     */
    public static function buildPossibleKeyFields($fields)
    {
        $fields = array_unique($fields);
        return implode(',', $fields);
    }

    /**
     * Check if a field is valid and return its description text
     * @param string $key_field Field to be validate
     * @return string Field description
     * @throws InvalidArgumentException Field is not valid
     */
    public static function validateKeyField($key_field)
    {
        if(!isset(self::$valid_key_fields[$key_field]))
            throw new InvalidArgumentException('Invalid key field: '.$key_field);
        else
            return self::$valid_key_fields[$key_field];
    }

    /**
     * @param string $key_fields_string Comma separated string with field names
     * @param string $field Field name to append
     * @return string Comma separated string with field names
     */
    public static function appendKeyField($key_fields_string, $field)
    {
        $fields = self::parsePossibleKeyFields($key_fields_string);
        $fields[] = $field;
        return self::buildPossibleKeyFields($fields);
    }
}