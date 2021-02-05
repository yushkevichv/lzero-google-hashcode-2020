<?php

namespace App\Models;


class Library
{
    public int $id;
    public int $totalBooksCount;
    public int $signupDays;
    public int $processPerDay;
    public \Illuminate\Support\Collection $books;

    public function __construct(int $id, int $totalBooksCount, int $signupDays, int $processPerDay, array $books = [])
    {
        $this->id = $id;
        $this->totalBooksCount = $totalBooksCount;
        $this->signupDays = $signupDays;
        $this->processPerDay = $processPerDay;
        $this->books = collect([]);
    }

    public function setBooks(array $books)
    {
        $this->books = collect($books)->sortByDesc('score');
    }

}