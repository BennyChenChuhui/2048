<?php

namespace Yitznewton\TwentyFortyEight;

class MoveCalculator
{
    const EMPTY_CELL = -1;

    private $grid;

    /**
     * @param int[][] $grid
     */
    public function __construct(array $grid)
    {
        $this->grid = $grid;
    }

    /**
     * @return bool
     */
    public function hasPossibleMoves()
    {
        $rotater = new GridRotater();
        $grid = $this->grid;

        $possibilityByMove = array_map(function ($move) use ($rotater, $grid) {
            $grid = $rotater->rotateForMove($grid, $move);

            return array_reduce($grid, function ($carry, $row) {
                return $carry || $row != $this->collapseAndPadRow($row);
            }, false);
        }, Move::getAll());

        return (bool) array_filter($possibilityByMove);
    }

    /**
     * @param mixed $move
     * @return bool
     */
    public function isPossibleMove($move)
    {
        return $this->makeMove($move) != $this->grid;
    }

    /**
     * @param mixed $move One of the values in the Move pseudo-enum
     * @return array
     */
    public function makeMove($move)
    {
        $rotater = new GridRotater($move);

        $rotatedGrid = $rotater->rotateForMove($this->grid, $move);

        $calculatedGrid = array_map(function ($row) {
            return $this->collapseAndPadRow($row);
        }, $rotatedGrid);

        return $rotater->unrotateForMove($calculatedGrid, $move);
    }

    private function collapseAndPadRow($row)
    {
        $collapsedRow = $this->collapseRow($row);
        return $this->padRowWithEmptyCells($collapsedRow, count($row));
    }

    private function collapseRow($row)
    {
        if ($row === []) {
            return [];
        }

        if ($row[0] == EMPTY_CELL) {
            return $this->collapseRow(array_slice($row, 1));
        }

        if (count($row) === 1) {
            return $row;
        }

        if ($row[1] == EMPTY_CELL) {
            return $this->collapseRow(array_merge([$row[0]], array_slice($row, 2)));
        }

        if ($row[0] == $row[1]) {
            $sum = $row[0] + $row[1];
            return array_merge([$sum], $this->collapseRow(array_slice($row, 2)));
        }

        return array_merge([$row[0]], $this->collapseRow(array_slice($row, 1)));
    }

    private function padRowWithEmptyCells($row, $size)
    {
        while (count($row) < $size) {
            array_push($row, EMPTY_CELL);
        }

        return $row;
    }
}