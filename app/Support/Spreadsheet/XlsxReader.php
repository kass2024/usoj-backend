<?php

namespace App\Support\Spreadsheet;

use RuntimeException;
use ZipArchive;

class XlsxReader
{
    /** @var array<int, string> */
    protected array $sheetNames = [];

    /** @var array<int, array<int, array<int, string>>> */
    protected array $sheetRows = [];

    public static function fromFile(string $path): self
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Invalid or unreadable Excel file.');
        }

        try {
            $reader = new self();
            $reader->parse($zip);

            return $reader;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    public function sheetNames(): array
    {
        return $this->sheetNames;
    }

    /** @return array<int, array<int, string>> */
    public function rows(int $sheetIndex = 0): array
    {
        return $this->sheetRows[$sheetIndex] ?? [];
    }

    public function sheetIndexByName(string $name): ?int
    {
        foreach ($this->sheetNames as $index => $sheetName) {
            if (strcasecmp($sheetName, $name) === 0) {
                return $index;
            }
        }

        return null;
    }

    protected function parse(ZipArchive $zip): void
    {
        $sharedStrings = $this->readSharedStrings($zip);
        $workbookXml = $this->readZipEntry($zip, 'xl/workbook.xml');
        $relsXml = $this->readZipEntry($zip, 'xl/_rels/workbook.xml.rels');

        if ($workbookXml === '' || $relsXml === '') {
            throw new RuntimeException('Workbook metadata is missing from the Excel file.');
        }

        $targets = $this->parseWorkbookTargets($workbookXml, $relsXml);

        foreach ($targets as $index => $target) {
            $path = $target['path'];
            $sheetPath = str_starts_with($path, 'xl/') ? $path : 'xl/'.$path;
            $sheetXml = $this->readZipEntry($zip, $sheetPath);
            $this->sheetNames[$index] = $target['name'];
            $this->sheetRows[$index] = $this->parseSheetRows($sheetXml, $sharedStrings);
        }
    }

    /** @return array<int, string> */
    protected function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $this->readZipEntry($zip, 'xl/sharedStrings.xml');
        if ($xml === '') {
            return [];
        }

        $doc = $this->loadXml($xml);
        if (! $doc) {
            return [];
        }

        $strings = [];
        foreach ($doc->getElementsByTagName('si') as $si) {
            $strings[] = $this->textContent($si);
        }

        return $strings;
    }

    /**
     * @return array<int, array{name: string, path: string}>
     */
    protected function parseWorkbookTargets(string $workbookXml, string $relsXml): array
    {
        $workbook = $this->loadXml($workbookXml);
        $rels = $this->loadXml($relsXml);
        if (! $workbook || ! $rels) {
            throw new RuntimeException('Could not parse workbook XML.');
        }

        $relMap = [];
        foreach ($rels->getElementsByTagName('Relationship') as $rel) {
            if (! $rel instanceof \DOMElement) {
                continue;
            }
            $relMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
        }

        $targets = [];
        foreach ($workbook->getElementsByTagName('sheet') as $sheet) {
            if (! $sheet instanceof \DOMElement) {
                continue;
            }
            $rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            if ($rid === '' || ! isset($relMap[$rid])) {
                continue;
            }
            $targets[] = [
                'name' => $sheet->getAttribute('name'),
                'path' => $relMap[$rid],
            ];
        }

        return $targets;
    }

    /** @return array<int, array<int, string>> */
    protected function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        if ($sheetXml === '') {
            return [];
        }

        $doc = $this->loadXml($sheetXml);
        if (! $doc) {
            return [];
        }

        $rows = [];
        foreach ($doc->getElementsByTagName('row') as $rowNode) {
            if (! $rowNode instanceof \DOMElement) {
                continue;
            }

            $row = [];
            foreach ($rowNode->getElementsByTagName('c') as $cell) {
                if (! $cell instanceof \DOMElement) {
                    continue;
                }

                $ref = $cell->getAttribute('r');
                $colIndex = $this->columnIndexFromCellRef($ref);
                $row[$colIndex] = $this->cellValue($cell, $sharedStrings);
            }

            if ($row !== []) {
                ksort($row);
                $max = max(array_keys($row));
                $normalized = [];
                for ($i = 0; $i <= $max; $i++) {
                    $normalized[] = (string) ($row[$i] ?? '');
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    protected function cellValue(\DOMElement $cell, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');

        if ($type === 's') {
            $index = (int) $this->firstTagText($cell, 'v');

            return (string) ($sharedStrings[$index] ?? '');
        }

        if ($type === 'inlineStr') {
            foreach ($cell->getElementsByTagName('t') as $textNode) {
                return $textNode->textContent ?? '';
            }

            return '';
        }

        return $this->firstTagText($cell, 'v');
    }

    protected function firstTagText(\DOMElement $parent, string $tag): string
    {
        foreach ($parent->getElementsByTagName($tag) as $node) {
            return $node->textContent ?? '';
        }

        return '';
    }

    protected function columnIndexFromCellRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/', strtoupper($ref), $matches)) {
            return 0;
        }

        $letters = $matches[1];
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    protected function textContent(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 't') {
                $text .= $child->textContent ?? '';
            } elseif ($child->hasChildNodes()) {
                $text .= $this->textContent($child);
            }
        }

        return $text;
    }

    protected function readZipEntry(ZipArchive $zip, string $name): string
    {
        $content = $zip->getFromName($name);

        return $content === false ? '' : $content;
    }

    protected function loadXml(string $xml): ?\DOMDocument
    {
        $doc = new \DOMDocument();
        if (! @$doc->loadXML($xml)) {
            return null;
        }

        return $doc;
    }
}
