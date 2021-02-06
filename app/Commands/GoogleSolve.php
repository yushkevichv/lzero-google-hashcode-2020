<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GoogleSolve extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'hashcode:solve';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Magic solver hashcode competition';

    public \App\Models\Limits $limits;
    public \Illuminate\Support\Collection $libraries;
    public array $books;
    public \App\Models\Solution $solution;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // @todo move to param
        $inputFile = storage_path('input/a_example.txt');
        if(!is_file($inputFile)) {
            $this->error("invalid input file");
            die(1);
        }

        $this->task("Fill data from file", function () use ($inputFile) {
            $this->fillDataFromFile($inputFile);
            return true;
        });

        $this->task("Calculate optimal solution", function() {
            $this->solve();
            return true;
        });


        $this->task("Outputting", function () {
            $this->info("Libraries count: ". $this->solution->libraries->count());
            foreach($this->solution->booksFromLibrary as $libraryId => $booksFromLibrary)
            {
                $this->info("Signup library $libraryId and send count books: ". count($booksFromLibrary));
                $this->info("Send books to processing: ". implode(' ', $booksFromLibrary));
                $this->info("Total score is: ". $this->solution->getTotalScore());

            }
            return true;
        });
    }

    private function solve() : void
    {
        $this->solution = new \App\Models\Solution();

        while($this->libraries->isNotEmpty() && $this->limits->daysLimit > 0)
        {
            $this->recalcLibraryScore();
            /** @var \App\Models\Library $library */
            $library = $this->libraries->shift();
            if($library->score  == 0) {
                break;
            }

            $this->solution->addLibrary($library, $this->limits->daysLimit);
            $this->limits->daysLimit -= $library->signupDays;

            if ($library->books->isEmpty()) {
                continue;
            }
        }
    }

    private function recalcLibraryScore()
    {
        $this->libraries->each(function ($library) {
            $processeedBooks = $this->solution->getAllProcessedBooks();
            $library->books->whereIn('id', $processeedBooks)->each(function(\App\Models\Book $book) {
                $book->score = 0;
            });
//            $library->setBooks($library->books->whereNotIn('id', $processeedBooks));
            $library->calculateScore($this->limits->daysLimit);
        });

        $this->libraries = $this->libraries->sortByDesc('score');
    }

    private function fillDataFromFile(string $filePath) : void
    {
        $fileHandle = fopen($filePath, 'r');

        $this->limits = new \App\Models\Limits(...$this->unpack(fgets($fileHandle)));
        $booksScore = $this->unpack(fgets($fileHandle));

        for($i = 0; $i < $this->limits->booksCount; $i++)
        {
            $this->books[] = new \App\Models\Book($i, (int) $booksScore[$i]);
        }

        $this->libraries = collect([]);
        for($i = 0; $i < $this->limits->librariesCount; $i++)
        {
            $library = new \App\Models\Library($i, ...$this->unpack(fgets($fileHandle)));
            $library->setBooks(collect($this->books)->whereIn('id', $this->unpack(fgets($fileHandle))));
            $library->calculateScore($this->limits->daysLimit);
            $this->libraries->push($library);
        }

        fclose($fileHandle);
    }

    private function unpack(string $line) :array
    {
        return explode(' ',trim($line));
    }
}
