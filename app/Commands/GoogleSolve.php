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
    public array $libraries;
    public array $books;

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

        $dataSet = $this->getDataFromFile($inputFile);

        $this->task("Installing Laravel", function () {
            $this->info('Simplicity is the ultimate sophistication.');
            return true;
        });

        $this->task("Doing something else", function () {
            return false;
        });
        $this->comment('test comment');
        $this->question('to be or not to be? ');
    }

    private function fillDataFromFile(string $filePath) : void
    {
        $file = fopen($filePath, 'r');

        $this->limits = new \App\Models\Limits(...$this->unpack(fgets($file)));
        $booksScore = $this->unpack(fgets($file));

        for($i = 0; $i < $this->limits->booksCount; $i++)
        {
            $this->books[] = new \App\Models\Book($i, (int) $booksScore[$i]);
        }


        for($i = 0; $i < $this->limits->librariesCount; $i++)
        {
            $library = new \App\Models\Library($i, ...$this->unpack(fgets($file)));
            $library->setBooks($this->unpack(fgets($file)));
            $this->libraries[] = $library;
        }
    }

    private function unpack(string $line) :array
    {
        return explode(' ',trim($line));
    }
}
