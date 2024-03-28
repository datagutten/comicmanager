<?php

namespace datagutten\comicmanager;

use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\elements\Strip;
use datagutten\tools;
use datagutten\tools\files\files;
use FilesystemIterator;

class Lists
{
    /**
     * @var string Path to comic lists
     */
    public string $list_path;
    public comicmanager $comicmanager;

    public function __construct(string $list_path)
    {
        $this->list_path = realpath($list_path);
    }

    public function lists(string $comic_id, string $subdir = null): array
    {
        $list_path = tools\files\files::path_join($this->list_path, $comic_id);
        if ($subdir)
            $list_path = tools\files\files::path_join($list_path, $subdir);
        if (!file_exists($list_path))
            return [[], []];

        $files = [];
        $lists = [];
        $folders = [];
        $iterator = new FilesystemIterator($list_path);
        foreach ($iterator as $file)
        {
            $relative_path = str_replace($list_path, '', $file->getPath());
            $files[$relative_path] = $file->getBasename('.txt');
            if ($file->isFile())
                $lists[] = $file->getBasename('.txt');
            elseif ($file->isDir())
                $folders[] = $file->getBasename();

        }
        return [$lists, $folders];
    }

    public function list_file(string $comic_id, string $list, string $folder = null): string
    {
        return files::path_join($this->list_path, $comic_id, $folder, $list . '.txt');
    }

    /**
     * @param string $data List file content
     * @param string $default_comic Default comic for lines not specifying any comic
     * @return Release[]
     * @throws exceptions\ComicNotFound
     * @throws exceptions\DatabaseException
     * @throws exceptions\comicManagerException
     */
    public function parse_list(string $data, string $default_comic = ''): array
    {
        $lines = explode("\n", $data);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        if (!empty($default_comic))
            $comic = $this->comicmanager->comicinfo($default_comic);

        $releases = [];
        foreach ($lines as $key => $line)
        {
            $comment_pos = strpos($line, '#');
            if (!isset($this->comicmanager->comic_info))
                continue;
            if ($line[0] == 'x' || $line[0] == '#') //Comment line
                continue;
            if ($line[-1] == ':')
            {
                try
                {
                    $this->comicmanager->comicinfo(substr($line, 0, -1));
                }
                catch (exceptions\ComicNotFound $e)
                {
                    $releases[] = ['error' => $e->getMessage()];
                    unset($this->comicmanager->comic_info);
                }
            } elseif (preg_match('^([0-9]{8})(?:\s+-\s+[0-9]+)*\s+-\s+([a-z]+)^', $line, $date_and_site)) //date and site
            {
                $releases[] = Release::from_date($this->comicmanager, $date_and_site[2], $date_and_site[1]);
            } elseif (preg_match('#^([0-9]+)#', $line, $matches))
            {
                $strip = Strip::from_grouping_key($this->comicmanager, (int)$matches[1]);
                $strip->key_field = 'id';
                try
                {
                    $releases[] = $strip->latest();
                }
                catch (exceptions\StripNotFound $e)
                {
                    if ($comment_pos !== false)
                        $comment = trim(substr($line, $comment_pos + 1));
                    else
                        $comment = null;

                    $releases[] = ['error' => sprintf('Strip not found: %s %s %s %s', $strip->comic->id, $strip->key_field, $strip->key, $comment ?? '')];
                }
            } else
                continue;
        }
        return $releases;
    }

    /**
     * @param $comic
     * @param $file
     * @param ?string $folder
     * @return Release[]
     * @throws exceptions\ComicNotFound
     * @throws exceptions\DatabaseException
     * @throws exceptions\comicManagerException
     */
    public function parse_list_file($comic, $file, string $folder = null): array
    {
        $data = $this->list_file($comic, $file, $folder);
        $data = file_get_contents($data);
        $encoding = mb_check_encoding($data, 'utf8');
        if (!$encoding)
            throw new exceptions\comicManagerException('Bad encoding');

        return $this->parse_list($data, $comic);
    }
}