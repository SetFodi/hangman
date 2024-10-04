<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PlayHangman extends Command
{
    // The name and signature of the console command
    protected $signature = 'play:hangman';

    // The description of the console command
    protected $description = 'Play a game of Hangman';

    // Create a new command instance
    public function __construct()
    {
        parent::__construct();
    }

    // The game logic goes here
    public function handle()
    {
        $this->info('Welcome to Hangman!');
        $this->playGame();
    }

    // Play the game
    protected function playGame()
    {
        $secretWord = $this->secret('Player 1, enter the secret word:');
        $maskedWord = str_repeat('_', strlen($secretWord));

        $this->info("\nPlayer 2, start guessing the word!");
        $this->info($maskedWord);

        $guesses = [];
        $lives = 6;
        $hangmanComplete = false;

        while ($lives > 0 && !$hangmanComplete) {
            $this->guessLetter($secretWord, $maskedWord, $guesses, $lives, $hangmanComplete);
        }
    }

    // Handle letter guessing
    protected function guessLetter(&$secretWord, &$maskedWord, &$guesses, &$lives, &$hangmanComplete)
    {
        $letter = $this->ask('Guess a letter:');

        if (in_array($letter, $guesses)) {
            $this->warn("You've already guessed that letter!");
        } else {
            $guesses[] = $letter;

            if (strpos($secretWord, $letter) !== false) {
                $this->info("Good guess!");
                $maskedWord = $this->revealLetters($secretWord, $maskedWord, $letter);
            } else {
                $lives--;
                $this->warn("Wrong guess! You have $lives lives left.");
            }
        }

        $this->info($maskedWord);

        if ($maskedWord == $secretWord) {
            $hangmanComplete = true;
            $this->info("Congratulations! You've guessed the word!");
            $this->logGame($secretWord, $guesses, 'win');
            $this->askForRestart();
        } elseif ($lives == 0) {
            $this->warn("You have been hanged! The word was: $secretWord");
            $this->logGame($secretWord, $guesses, 'lose');
            $this->askForRestart();
        }
    }

    // Reveal correct letters in the word
    protected function revealLetters($secretWord, $maskedWord, $letter)
    {
        for ($i = 0; $i < strlen($secretWord); $i++) {
            if ($secretWord[$i] == $letter) {
                $maskedWord[$i] = $letter;
            }
        }
        return $maskedWord;
    }

    // Log the game results
    protected function logGame($secretWord, $guesses, $result)
    {
        $logData = [
            'word' => $secretWord,
            'guesses' => implode(',', $guesses),
            'result' => $result,
            'time' => now()->toDateTimeString()
        ];

        Storage::append('hangman.log', json_encode($logData));
    }

    // Ask if the players want to restart
    protected function askForRestart()
    {
        $restart = $this->confirm('Do you want to restart the game?');

        if ($restart) {
            $this->playGame();
        } else {
            $this->info("Thanks for playing!");
        }
    }
}
