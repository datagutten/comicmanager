<?php


namespace datagutten\comicmanager;


class files
{
    /**
     * Try different extensions for a file name
     * @param string $filename File name
     * @return string File name with extension
     * @throws comicsException File not found
     */
    public static function typecheck($filename)
    {
        $types = array('jpg', 'gif', 'png');
        foreach ($types as $type)
        {
            if (file_exists($filename . '.' . $type))
            {
                $file = $filename . ".$type";
                break;
            }
        }
        if (!isset($file)) //File not found
            throw new comicsException('Image not found by date: ' . $filename);

        return $file;
    }
}