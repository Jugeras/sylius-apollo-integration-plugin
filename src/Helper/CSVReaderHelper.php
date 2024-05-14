<?php

namespace PrintPlius\SyliusApolloIntegrationPlugin\Helper;

class CSVReaderHelper
{
    private \SplFileObject $fileObject;
    private string $enclosure;
    private array $fields;

    public function __construct(string $filename, string $separator = '|', array $fields = [], $enclosure = '"')
    {
        if ($separator === 'auto') {
            $separator = $this->detectDelimiter($filename);
        }

        $this->fileObject = new \SplFileObject($filename);
        $this->fileObject->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::READ_AHEAD
            | \SplFileObject::DROP_NEW_LINE
        );
        $this->fileObject->setCsvControl($separator, $enclosure);
        $this->enclosure = $enclosure;
        $this->fields = $fields;
    }

    private function detectDelimiter($csvFile)
    {
        $delimiters = [',' => 0, '|' => 0];
        $firstLine = '';
        $handle = fopen($csvFile, 'r');
        if ($handle) {
            $firstLine = fgets($handle);
            fclose($handle);
        }
        if ($firstLine) {
            foreach ($delimiters as $delimiter => &$count) {
                $count = count(str_getcsv($firstLine, $delimiter));
            }
            return array_search(max($delimiters), $delimiters);
        } else {
            return key($delimiters);
        }
    }

    public function getCount(): int
    {
        $this->fileObject->seek(PHP_INT_MAX);
        $lineCount = $this->fileObject->key() + 1;
        $this->fileObject->rewind();

        return $lineCount;
    }

    public function getData(): \Generator
    {
        $columnNames = [];
        $this->fileObject->rewind();
        foreach ($this->fileObject as $row) {
            if (empty($columnNames)) {
                $columnNames = $row;
                $columnNames = array_map(function ($columnName) {
                    return trim(trim($columnName, "\xEF\xBB\xBF"), $this->enclosure);
                }, $columnNames);
                continue;
            }

            if (!empty($this->fields)) {
                $tmpData = [];
                foreach ($columnNames as $i => $name) {
                    if (!in_array($name, $this->fields)) {
                        continue;
                    }

                    $tmpData[$name] = $row[$i] ?? '';
                }
                yield $tmpData;
            } else {
                if(count($columnNames) == count($row)) {
                    yield array_combine($columnNames, $row);
                } else {
                    var_dump($columnNames, $row);
                }
            }
        }
    }

    public function findOneBy(array $conditions)
    {
        foreach ($this->getData() as $datum) {
            $conditionsFulfilled = true;
            foreach ($conditions as $name => $value) {
                if ($datum[$name] !== $value) {
                    $conditionsFulfilled = false;
                }
            }

            if ($conditionsFulfilled) {
                return $datum;

            }
        }

        return null;
    }


}
