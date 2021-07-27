<?php


namespace datagutten\comicmanager\Queries;


use datagutten\comicmanager\elements;
use PDO;


class Strip extends Common
{
    public function range(elements\Comic $comic, int $from, int $to, ?string $key_field = null): array
    {
        if (empty($key_field))
            $key_field = $comic->key_field;

        $query = $this->connection->newQuery()
            ->select($key_field)
            ->from($comic->id)
            ->where([
                $key_field . ' >=' => $from,
                $key_field . ' <=' => $to
            ])->distinct($key_field);

        return $this->execute($query)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function latest(elements\Comic $comic, $key, $key_field = null)
    {
        if (empty($key_field))
            $key_field = $comic->key_field;

        $query = $this->connection->newQuery()
            ->select('*')
            ->from($comic->id)
            ->where([$key_field => $key])
            ->order(['date' => 'DESC'])
            ->limit(1);

        return $this->execute($query);
    }

    public function key(elements\Comic $comic, $key, $key_field = null)
    {
        if (empty($key_field))
            $key_field = $comic->key_field;

        $query = $this->connection->newQuery()
            ->select('*')
            ->from($comic->id)
            ->where([$key_field => $key,]);
        return $this->execute($query);
    }

    /**
     * Get highest and lowest value for a field
     * @param elements\Comic $comic
     * @param ?string $key_field Key field
     * @return array
     */
    function key_high_low(elements\Comic $comic, string $key_field = null): array
    {
        if (empty($key_field))
            $key_field = $comic->key_field;

        $query = $this->connection->newQuery()->from($comic->id);
        $func = $query->func();

        $st = $query->select(['min' => $func->min($key_field), 'max' => $func->max($key_field)])->execute();
        return $st->fetch('assoc');
    }
}