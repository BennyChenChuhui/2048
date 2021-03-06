<?php

namespace Yitznewton\TwentyFortyEight;

use Yitznewton\TwentyFortyEight\Input\Input;
use Yitznewton\TwentyFortyEight\Input\UnrecognizedInputException;
use Yitznewton\TwentyFortyEight\Move\ImpossibleMoveException;
use Yitznewton\TwentyFortyEight\Move\MoveMaker;
use Yitznewton\TwentyFortyEight\Move\Scorer;
use Yitznewton\TwentyFortyEight\Output\Output;

class Game
{
    private $size;
    private $winningTile;
    private $input;
    private $output;
    private $scorer;

    /**
     * @param int $size
     * @param int $winningTile
     * @param Input $input
     * @param Output $output
     */
    public function __construct($size, $winningTile, Input $input, Output $output)
    {
        $this->size = $size;
        $this->winningTile = $winningTile;
        $this->input = $input;
        $this->output = $output;
        $this->scorer = new Scorer();
    }

    public function run()
    {
        $grid = $this->createGrid($this->size);
        $grid = $this->injectRandom($grid, 2);

        $this->output->renderBoard($grid, $this->scorer->getScore());

        $moveMaker = $this->getMoveMaker($grid);

        while ($moveMaker->hasPossibleMoves()) {
            try {
                $move = $this->input->getMove($grid);
            } catch (UnrecognizedInputException $e) {
                // ignore this input, and listen again
                continue;
            }

            try {
                $grid = $this->takeTurn($grid, $move, $moveMaker);
            } catch (ImpossibleMoveException $e) {
                // ignore
                continue;
            }

            $this->output->renderBoard($grid, $this->scorer->getScore());

            if ($this->hasWinningTile($grid)) {
                $this->output->renderWin($this->winningTile);
                break;
            }

            $moveMaker = $this->getMoveMaker($grid);
        }

        $this->output->renderGameOver($this->scorer->getScore());
    }

    private function injectRandom($grid, $numberOfCells)
    {
        $randomChoices = [2,4];

        for ($i = 0; $i < $numberOfCells; $i++) {
            $randomNumber = $randomChoices[rand(0, 1)];
            $grid = $grid->replaceRandom(Grid::EMPTY_CELL, $randomNumber);
        }

        return $grid;
    }

    /**
     * @param int $size
     * @return Grid
     */
    private function createGrid($size)
    {
        return Grid::fromArray(array_map(function () use ($size) {
            return array_fill(0, $size, Grid::EMPTY_CELL);
        }, range(0, $size-1)));
    }


    private function hasWinningTile($grid)
    {
        return $grid->reduce(function ($carry, $cell) {
            return $carry || $cell >= $this->winningTile;
        }, false);
    }

    private function takeTurn($grid, $move, $moveMaker)
    {
        $grid = $moveMaker->makeMove($move);
        $grid = $this->injectRandom($grid, 1);

        return $grid;
    }

    private function getMoveMaker($grid)
    {
        $moveMaker = new MoveMaker($grid);
        $moveMaker->addListener($this->scorer);
        return $moveMaker;
    }
}
