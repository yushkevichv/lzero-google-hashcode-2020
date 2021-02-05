<?php

namespace App\Models;


class Limits
{
    public int $booksCount;
    public int $librariesCount;
    public int $daysLimit;

    public function __construct($booksCount, $librariesCount, $daysLimit)
    {
        $this->booksCount = $booksCount;
        $this->librariesCount = $librariesCount;
        $this->daysLimit = $daysLimit;
    }

}