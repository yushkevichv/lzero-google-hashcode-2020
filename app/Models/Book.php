<?php

namespace App\Models;


class Book
{
    public int $id;
    public int $score;

    public function __construct(int $id, int $score)
    {
        $this->id = $id;
        $this->score = $score;
    }

}