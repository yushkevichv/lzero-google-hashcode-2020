<?php

namespace App\Models;


class Library
{
    public int $id;
    public int $totalBooksCount;
    public int $signupDays;
    public int $processPerDay;
    public float $score;
    public \Illuminate\Support\Collection $books;

    public function __construct(int $id, int $totalBooksCount, int $signupDays, int $processPerDay, array $books = [])
    {
        $this->id = $id;
        $this->totalBooksCount = $totalBooksCount;
        $this->signupDays = $signupDays;
        $this->processPerDay = $processPerDay;
        $this->books = collect([]);
    }

    public function setBooks(\Illuminate\Support\Collection $books) : void
    {
        $this->books = $books->sortByDesc('score');
    }

    public function sortBooks(): void
    {
        $this->books = $this->books->sortByDesc('score');
    }

    public function calculateScore(int $daysLimit) :void
    {
        $this->score = 0;
        $booksScore = $this->calculateBooksScore($daysLimit);

        if($booksScore == 0)
        {
            return;
        }

        // test for more accuracy variants
        $this->score = $booksScore / $this->signupDays;
    }

    public function calculateBooksScore(int $daysLimit) :int
    {
        if($daysLimit <= $this->signupDays)
        {
            return 0;
        }

        $potentialProcessed = ($daysLimit - $this->signupDays) * $this->processPerDay;
        $countProcessedBooks = ($this->totalBooksCount > $potentialProcessed) ? $potentialProcessed : $this->totalBooksCount;

        // test for more accuracy variants
        return  $this->books->slice(0, $countProcessedBooks)->sum('score');

    }

}