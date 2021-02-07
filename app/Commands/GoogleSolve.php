<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class GoogleSolve extends Command
{
    public \App\Models\Limits $limits;
    public \Illuminate\Support\Collection $libraries;
    public array $books;
    public \App\Models\Solution $solution;
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $timeStart = microtime(true);
        // @todo move to param
//        $inputFile = storage_path('input/a_example.txt');
        $inputFile = storage_path('input/b_read_on.txt');
//        $inputFile = storage_path('input/c_incunabula.txt');
//        $inputFile = storage_path('input/d_tough_choices.txt');
        if (!is_file($inputFile)) {
            $this->error("invalid input file");
            die(1);
        }

        $this->task("Fill data from file", function () use ($inputFile) {
            $this->fillDataFromFile($inputFile);
            return true;
        });

        $this->task("Calculate optimal solution", function () {
            $this->solve();
            return true;
        });


        $this->task("Outputting", function () {
            $this->outputResult();
            return true;
        });

        $timeEnd = microtime(true);
        $this->comment("Process solving take: ".($timeEnd - $timeStart)." sec");
    }

    private function fillDataFromFile(string $filePath): void
    {
        $fileHandle = fopen($filePath, 'r');

        $this->limits = new \App\Models\Limits(...$this->unpack(fgets($fileHandle)));
        $booksScore = $this->unpack(fgets($fileHandle));

        $progressBar = $this->createProgressBar($this->limits->booksCount);

        for ($i = 0; $i < $this->limits->booksCount; $i++) {
            $this->books[$i] = new \App\Models\Book($i, (int) $booksScore[$i]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->comment("books filled");

        $this->libraries = collect([]);
        $progressBar = $this->createProgressBar($this->limits->booksCount);
        for ($i = 0; $i < $this->limits->librariesCount; $i++) {
            $library = new \App\Models\Library($i, ...$this->unpack(fgets($fileHandle)));
            $books = [];
            foreach ($this->unpack(fgets($fileHandle)) as $bookId) {
                $books[] = $this->books[$bookId];
            }
            $library->setBooks(collect($books));
            $this->libraries->push($library);
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->comment("libraries filled");

        fclose($fileHandle);
    }

    private function unpack(string $line): array
    {
        return explode(' ', trim($line));
    }

    private function createProgressBar($maxSteps): \Symfony\Component\Console\Helper\ProgressBar
    {
        $progressBar = new \Symfony\Component\Console\Helper\ProgressBar($this->output, $maxSteps);
        $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
        $progressBar->setProgressCharacter('');
        $progressBar->setBarCharacter('▓'); // dark shade character \u2593
        $progressBar->setFormat(" %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% \n");
        return $progressBar;
    }

    private function solve(): void
    {
        $this->solution = new \App\Models\Solution();

        $progressBar = $this->createProgressBar($this->libraries->count());
        while ($this->libraries->isNotEmpty() && $this->limits->daysLimit > 0) {
            $this->recalcLibraryScore();
            /** @var \App\Models\Library $library */
            $library = $this->libraries->shift();
            if ($library->score == 0) {
                break;
            }

            $processedBooks = $library->books->pluck('id')->toArray();
            $this->solution->addLibrary($library, $processedBooks, $this->limits->daysLimit);
            foreach ($processedBooks as $book) {
                $this->books[$book]->score = 0;
            }
            $this->limits->daysLimit -= $library->signupDays;

            $progressBar->advance();
            if ($library->books->isEmpty()) {
                continue;
            }
        }
        $progressBar->finish();
    }

    private function recalcLibraryScore()
    {
        $this->libraries->each(function ($library) {
            $library->calculateScore($this->limits->daysLimit);
        });

        $this->libraries = $this->libraries->sortByDesc('score');
    }

    private function outputResult(): void
    {
        $this->info("Libraries count: ".$this->solution->libraries->count());
        foreach ($this->solution->booksFromLibrary as $libraryId => $booksFromLibrary) {
            $this->info("Signup library $libraryId and send count books: ".count($booksFromLibrary));
            $this->info("Send books to processing: ".implode(' ', $booksFromLibrary));
        }
        $this->info("Total score is: ".$this->solution->getTotalScore());
    }
}
