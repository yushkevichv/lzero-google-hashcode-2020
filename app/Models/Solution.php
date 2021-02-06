<?php

namespace App\Models;


class Solution
{

    public \Illuminate\Support\Collection $libraries;
    public \Illuminate\Support\Collection $booksFromLibrary;
    public int $totalScore = 0;

    public function __construct()
    {
        $this->libraries = collect([]);
        $this->booksFromLibrary = collect([]);
    }

    public function addLibrary(Library $library, int $daysLimit) : void
    {
        $this->libraries->push($library->id);
        $this->addBooksForLibrary($library, $library->books->pluck('id')->toArray());

        // test for more accuracy variants
        $this->totalScore += $library->calculateBooksScore($daysLimit);
    }

    public function addBooksForLibrary(Library $library, array $booksId) :void
    {
        $this->booksFromLibrary[$library->id] = $booksId;
    }

    public function getAllProcessedBooks() : array
    {
        return $this->booksFromLibrary->flatten()->unique()->toArray();
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

}