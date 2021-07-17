<?php


namespace datagutten\comicmanager\exceptions;


use datagutten\comicmanager\elements\Strip;
use Throwable;

class StripNotFound extends comicManagerException
{
    function __construct(Strip $strip, Throwable $previous = null)
    {
        if(!empty($strip->site) && !empty($strip->date))
            $message = sprintf('No strip found with site %s and date %s', $strip->site, $strip->date);
        else
            $message = sprintf('Strip not found');
        parent::__construct($message, 0, $previous);
    }
}