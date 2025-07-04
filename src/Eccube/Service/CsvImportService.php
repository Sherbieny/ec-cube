<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Service;

use Eccube\Stream\Filter\ConvertLineFeedFilter;
use Eccube\Stream\Filter\SjisToUtf8EncodingFilter;

/**
 * Copyright (C) 2012-2014 David de Boer <david@ddeboer.nl>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
class CsvImportService implements \Iterator, \SeekableIterator, \Countable
{
    public const DUPLICATE_HEADERS_INCREMENT = 1;
    public const DUPLICATE_HEADERS_MERGE = 2;

    /**
     * Number of the row that contains the column names
     *
     * @var int
     */
    protected $headerRowNumber;

    /**
     * CSV file
     *
     * @var \SplFileObject
     */
    protected $file;

    /**
     * Column headers as read from the CSV file
     *
     * @var array
     */
    protected $columnHeaders = [];

    /**
     * Number of column headers, stored and re-used for performance
     *
     * In case of duplicate headers, this is always the number of unmerged headers.
     *
     * @var int
     */
    protected $headersCount;

    /**
     * Total number of rows in the CSV file
     *
     * @var int
     */
    protected $count;

    /**
     * Faulty CSV rows
     *
     * @var array
     */
    protected $errors = [];

    /**
     * How to handle duplicate headers
     *
     * @var int
     */
    protected $duplicateHeadersFlag;

    /**
     * @param \SplFileObject $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct(\SplFileObject $file, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        // stream filter を適用して文字エンコーディングと改行コードの変換を行う
        // see https://github.com/EC-CUBE/ec-cube/issues/5252
        $filters = [
            ConvertLineFeedFilter::class,
        ];

        if (!\mb_check_encoding($file->current(), 'UTF-8')) {
            // UTF-8 が検出できなかった場合は SJIS-win の stream filter を適用する
            $filters[] = SjisToUtf8EncodingFilter::class;
        }

        $this->file = self::applyStreamFilter($file, ...$filters);
        $this->file->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::READ_AHEAD
        );
        $this->file->setCsvControl(
            $delimiter,
            $enclosure,
            $escape
        );
    }

    /**
     * Return the current row as an array
     *
     * If a header row has been set, an associative array will be returned
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        // If the CSV has no column headers just return the line
        if (empty($this->columnHeaders)) {
            return $this->file->current();
        }

        // Since the CSV has column headers use them to construct an associative array for the columns in this line
        if ($this->valid()) {
            $current = $this->file->current();

            $line = $current;

            // See if values for duplicate headers should be merged
            if (self::DUPLICATE_HEADERS_MERGE === $this->duplicateHeadersFlag) {
                $line = $this->mergeDuplicates($line);
            }

            // Count the number of elements in both: they must be equal.
            if (count($this->columnHeaders) === count($line)) {
                return array_combine(array_keys($this->columnHeaders), $line);
            } else {
                return $line;
            }
        }

        return null;
    }

    /**
     * Get column headers
     *
     * @return array
     */
    public function getColumnHeaders()
    {
        return array_keys($this->columnHeaders);
    }

    /**
     * Set column headers
     *
     * @param array $columnHeaders
     */
    public function setColumnHeaders(array $columnHeaders)
    {
        $this->columnHeaders = array_count_values($columnHeaders);
        $this->headersCount = count($columnHeaders);
    }

    /**
     * Set header row number
     *
     * @param int $rowNumber Number of the row that contains column header names
     * @param int $duplicates How to handle duplicates (optional). One of:
     *                        - CsvReader::DUPLICATE_HEADERS_INCREMENT;
     *                        increments duplicates (dup, dup1, dup2 etc.)
     *                        - CsvReader::DUPLICATE_HEADERS_MERGE; merges
     *                        values for duplicate headers into an array
     *                        (dup => [value1, value2, value3])
     *
     * @return bool
     */
    public function setHeaderRowNumber($rowNumber, $duplicates = null)
    {
        $this->duplicateHeadersFlag = $duplicates;
        $this->headerRowNumber = $rowNumber;
        $headers = $this->readHeaderRow($rowNumber);

        if ($headers === false) {
            return false;
        }
        $this->setColumnHeaders($headers);

        return true;
    }

    /**
     * Rewind the file pointer
     *
     * If a header row has been set, the pointer is set just below the header
     * row. That way, when you iterate over the rows, that header row is
     * skipped.
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->file->rewind();
        if (null !== $this->headerRowNumber) {
            $this->file->seek($this->headerRowNumber + 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if (null === $this->count) {
            $position = $this->key();

            $this->count = iterator_count($this);

            $this->seek($position);
        }

        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->file->next();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->file->valid();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->file->key();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function seek($pointer)
    {
        $this->file->seek($pointer);
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->getColumnHeaders();
    }

    /**
     * Get a row
     *
     * @param int $number Row number
     *
     * @return array
     */
    public function getRow($number)
    {
        $this->seek($number);

        return $this->current();
    }

    /**
     * Get rows that have an invalid number of columns
     *
     * @return array
     */
    public function getErrors()
    {
        if (0 === $this->key()) {
            // Iterator has not yet been processed, so do that now
            foreach ($this as $row) { /* noop */
            }
        }

        return $this->errors;
    }

    /**
     * Does the reader contain any invalid rows?
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * Stream filter を適用し, 新たな SplFileObject を返す.
     *
     * @param \SplFileObject $file Stream filter を適用する SplFileObject
     * @param \php_user_filter $filters 適用する stream filter のクラス名
     *
     * @return \SplFileObject 適用後の SplFileObject
     */
    public static function applyStreamFilter(\SplFileObject $file, string ...$filters): \SplFileObject
    {
        foreach ($filters as $filter) {
            \stream_filter_register($filter, $filter);
        }

        $tempFile = tmpfile();
        try {
            foreach ($filters as $filter) {
                \stream_filter_append($tempFile, $filter);
            }
            foreach ($file as $line) {
                fwrite($tempFile, $line);
            }
            $meta = \stream_get_meta_data($tempFile);

            return new \SplFileObject($meta['uri'], 'r');
        } finally {
            fclose($tempFile);
        }
    }

    /**
     * Read header row from CSV file
     *
     * @param int $rowNumber Row number
     *
     * @return array
     */
    protected function readHeaderRow($rowNumber)
    {
        $this->file->seek($rowNumber);
        $headers = $this->file->current();

        return $headers;
    }

    /**
     * Add an increment to duplicate headers
     *
     * So the following line:
     * |duplicate|duplicate|duplicate|
     * |first    |second   |third    |
     *
     * Yields value:
     * $duplicate => 'first', $duplicate1 => 'second', $duplicate2 => 'third'
     *
     * @param array $headers
     *
     * @return array
     */
    protected function incrementHeaders(array $headers)
    {
        $incrementedHeaders = [];
        foreach (array_count_values($headers) as $header => $count) {
            if ($count > 1) {
                $incrementedHeaders[] = $header;
                for ($i = 1; $i < $count; $i++) {
                    $incrementedHeaders[] = $header.$i;
                }
            } else {
                $incrementedHeaders[] = $header;
            }
        }

        return $incrementedHeaders;
    }

    /**
     * Merges values for duplicate headers into an array
     *
     * So the following line:
     * |duplicate|duplicate|duplicate|
     * |first    |second   |third    |
     *
     * Yields value:
     * $duplicate => ['first', 'second', 'third']
     *
     * @param array $line
     *
     * @return array
     */
    protected function mergeDuplicates(array $line)
    {
        $values = [];

        $i = 0;
        foreach ($this->columnHeaders as $count) {
            if (1 === $count) {
                $values[] = $line[$i];
            } else {
                $values[] = array_slice($line, $i, $count);
            }

            $i += $count;
        }

        return $values;
    }

    /**
     * 行の文字エンコーディングを変換する.
     *
     * Windows 版 PHP7 環境では、ファイルエンコーディングが CP932 になるため UTF-8 に変換する.
     * それ以外の環境では何もしない。
     *
     * @deprecated 使用していないため削除予定
     */
    protected function convertEncodingRows($row)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated.', E_USER_DEPRECATED);
        if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID >= 70000) {
            foreach ($row as &$col) {
                $col = mb_convert_encoding($col, 'UTF-8', 'SJIS-win');
            }
        }

        return $row;
    }
}
