<?php


namespace datagutten\comicmanager\exceptions;


use datagutten\comicmanager\elements\Release;
use Throwable;

class ReleaseNotFound extends comicManagerException
{
    function __construct(Release $release, Throwable $previous = null)
    {
        if(!empty($release->site) && !empty($release->date))
            $message = sprintf('No release found with site %s and date %s', $release->site, $release->date);
        else
            $message = sprintf('Release not found');
        parent::__construct($message, 0, $previous);
    }
}