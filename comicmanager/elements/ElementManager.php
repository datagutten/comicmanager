<?php


namespace datagutten\comicmanager\elements;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\Queries;

abstract class ElementManager
{
    /**
     * @var comicmanager
     */
    protected comicmanager $comicmanager;
    /**
     * @var Comic
     */
    public Comic $comic;

    function __construct(comicmanager $comicmanager)
    {
        $this->comicmanager = $comicmanager;
        $this->comic = $comicmanager->info;
    }
}