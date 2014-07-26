<?php

namespace Gasp\Render;

use Gasp\Exception;

class Table
{
    private $headers;

    private $rows;

    public function setHeaders(array $headers)
    {
        $this->headers = array_values($headers);

        return $this;
    }

    public function addRow(array $row)
    {
        if (!count($row)) {
            throw new Exception('Cannot pass empty array as row.');
        }

        $this->rows[] = array_values($row);

        return $this;
    }

    public function render()
    {
        if (!count($this->rows)) {
            return '';
        }

        $this->validateRows();

        $widths = $this->calculateColumnWidths();
        $output = '';

        if (count($this->headers)) {
            $output .= $this->renderHorizontalBorder($widths);
            $output .= $this->renderRow($this->headers, $widths);
        }

        $output .= $this->renderHorizontalBorder($widths);

        foreach ($this->rows as $row) {
            $output .= $this->renderRow($row, $widths);
        }

        $output .= $this->renderHorizontalBorder($widths);

        return $output;
    }

    protected function renderHorizontalBorder($widths)
    {
        return '+' . str_repeat('-', array_sum($widths)) . '+' . PHP_EOL;
    }

    protected function renderRow(array $row, array $widths)
    {
        $output = '|';

        foreach ($row as $index => $content) {
            $width = $widths[$index] - 2;

            $output .= sprintf(" %-{$width}s ", $content);
        }

        $output .= '|';
        $output .= PHP_EOL;

        return $output;
    }

    private function validateRows()
    {
        $columnCount = null;

        foreach ($this->rows as $row) {
            if ($columnCount && count($row) !== $columnCount) {
                throw new Exception('Rows do not have a consistent number of elements.');
            } elseif (!$columnCount) {
                $columnCount = count($row);
            }
        }

        if ($this->headers && count($this->headers) !== $columnCount) {
            throw new Exception('Rows do not have the some number of elements as there are headers.');
        }

        return true;
    }

    private function calculateColumnWidths()
    {
        $widths = array();
        $data   = $this->rows;

        // Need to check headers, too, they could be longer
        if (count($this->headers)) {
            $data[] = $this->headers;
        }

        foreach ($data as $row) {
            foreach ($row as $index => $column) {
                $len = strlen($column) + 4; // Adding 2 to accommodate for padding during render()

                if (!isset($widths[$index]) || $widths[$index] < $len) {
                    $widths[$index] = $len;
                }
            }
        }

        return $widths;
    }
}
