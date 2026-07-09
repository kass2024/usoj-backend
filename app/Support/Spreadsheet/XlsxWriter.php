<?php

namespace App\Support\Spreadsheet;

use RuntimeException;
use ZipArchive;

class XlsxWriter
{
    public const STYLE_DEFAULT = 0;

    public const STYLE_HEADER = 1;

    /** @var array<int, array{name: string, rows: array<int, array<int, string|int|float>>, options: array<string, mixed>}> */
    protected array $sheets = [];

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     * @param  array<string, mixed>  $options
     */
    public function addSheet(string $name, array $rows, array $options = []): self
    {
        $this->sheets[] = [
            'name' => mb_substr($name, 0, 31),
            'rows' => $rows,
            'options' => $options,
        ];

        return $this;
    }

    public function toString(): string
    {
        if ($this->sheets === []) {
            throw new RuntimeException('No worksheet data provided.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('Could not create temporary file.');
        }

        $path = $tmp.'.xlsx';
        @unlink($tmp);

        if (! $this->saveAs($path)) {
            throw new RuntimeException('Could not build Excel file.');
        }

        $binary = file_get_contents($path);
        @unlink($path);

        if ($binary === false) {
            throw new RuntimeException('Could not read generated Excel file.');
        }

        return $binary;
    }

    public function saveAs(string $path): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sharedStrings = [];
        $sharedIndex = [];
        $sheetXmlFiles = [];

        foreach ($this->sheets as $sheetIndex => $sheet) {
            $sheetXmlFiles[$sheetIndex] = $this->buildSheetXml(
                $sheet['rows'],
                $sheet['options'],
                $sharedStrings,
                $sharedIndex
            );
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($this->sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml());
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml(count($this->sheets)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStringsXml($sharedStrings));

        foreach ($sheetXmlFiles as $index => $xml) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $xml);
        }

        return $zip->close();
    }

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $sharedStrings
     * @param  array<string, int>  $sharedIndex
     */
    protected function buildSheetXml(array $rows, array $options, array &$sharedStrings, array &$sharedIndex): string
    {
        $xmlRows = [];
        $numericColumns = array_map('intval', $options['numericColumns'] ?? []);
        $headerRows = max(1, (int) ($options['headerRows'] ?? 1));

        foreach ($rows as $rowNumber => $row) {
            $cells = [];
            $isHeader = $rowNumber < $headerRows;
            $style = $isHeader ? self::STYLE_HEADER : self::STYLE_DEFAULT;

            foreach ($row as $colNumber => $value) {
                $ref = $this->cellRef($colNumber, $rowNumber);

                if (! $isHeader && in_array($colNumber, $numericColumns, true) && is_numeric($value)) {
                    $cells[] = '<c r="'.$ref.'" s="'.$style.'"><v>'.$value.'</v></c>';

                    continue;
                }

                $text = (string) $value;
                $key = $text;
                if (! array_key_exists($key, $sharedIndex)) {
                    $sharedIndex[$key] = count($sharedStrings);
                    $sharedStrings[] = $text;
                }
                $cells[] = '<c r="'.$ref.'" t="s" s="'.$style.'"><v>'.$sharedIndex[$key].'</v></c>';
            }

            $height = $isHeader ? ' ht="24"' : '';
            $xmlRows[] = '<row r="'.($rowNumber + 1).'"'.$height.'>'.implode('', $cells).'</row>';
        }

        $parts = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'];
        $parts[] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if (! empty($options['freezePane'])) {
            $parts[] = '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
                .'<pane ySplit="'.$headerRows.'" topLeftCell="A'.($headerRows + 1).'" activePane="bottomLeft" state="frozen"/>'
                .'</sheetView></sheetViews>';
        }

        if (! empty($options['columnWidths']) && is_array($options['columnWidths'])) {
            $parts[] = '<cols>';
            foreach ($options['columnWidths'] as $columnIndex => $width) {
                $min = (int) $columnIndex + 1;
                $parts[] = '<col min="'.$min.'" max="'.$min.'" width="'.(float) $width.'" customWidth="1"/>';
            }
            $parts[] = '</cols>';
        }

        $parts[] = '<sheetData>'.implode('', $xmlRows).'</sheetData>';

        if (! empty($options['autoFilter']) && $rows !== []) {
            $lastCol = $this->columnLetters(count($rows[0]) - 1);
            $parts[] = '<autoFilter ref="A1:'.$lastCol.$headerRows.'"/>';
        }

        if (! empty($options['validations']) && is_array($options['validations'])) {
            $validations = '';
            foreach ($options['validations'] as $validation) {
                $validations .= $this->buildDataValidationXml($validation);
            }
            if ($validations !== '') {
                $count = count($options['validations']);
                $parts[] = '<dataValidations count="'.$count.'">'.$validations.'</dataValidations>';
            }
        }

        $parts[] = '</worksheet>';

        return implode('', $parts);
    }

    /** @param  array<string, mixed>  $validation */
    protected function buildDataValidationXml(array $validation): string
    {
        $column = (int) ($validation['column'] ?? 0);
        $fromRow = (int) ($validation['fromRow'] ?? 2);
        $toRow = (int) ($validation['toRow'] ?? 500);
        $list = $validation['list'] ?? [];
        $colLetter = $this->columnLetters($column);
        $sqref = $colLetter.$fromRow.':'.$colLetter.$toRow;
        $formula = '"'.implode(',', array_map(static fn ($v) => str_replace('"', '""', (string) $v), $list)).'"';

        return '<dataValidation type="list" allowBlank="1" showDropDown="1" showInputMessage="1"'
            .' promptTitle="'.htmlspecialchars((string) ($validation['promptTitle'] ?? 'Choose a value'), ENT_XML1).'"'
            .' prompt="'.htmlspecialchars((string) ($validation['prompt'] ?? ''), ENT_XML1).'"'
            .' showErrorMessage="1"'
            .' errorTitle="'.htmlspecialchars((string) ($validation['errorTitle'] ?? 'Invalid value'), ENT_XML1).'"'
            .' error="'.htmlspecialchars((string) ($validation['error'] ?? 'Please select a value from the list.'), ENT_XML1).'"'
            .' sqref="'.$sqref.'">'
            .'<formula1>'.$formula.'</formula1>'
            .'</dataValidation>';
    }

    protected function cellRef(int $column, int $row): string
    {
        return $this->columnLetters($column).($row + 1);
    }

    protected function columnLetters(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    protected function contentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .$overrides
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    protected function workbookXml(): string
    {
        $sheets = '';
        foreach ($this->sheets as $index => $sheet) {
            $sheets .= '<sheet name="'.htmlspecialchars($sheet['name'], ENT_XML1).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets>'
            .'</workbook>';
    }

    protected function workbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $styleId = $sheetCount + 1;
        $rels .= '<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $stringsId = $sheetCount + 2;
        $rels .= '<Relationship Id="rId'.$stringsId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'</Relationships>';
    }

    protected function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F7A4D"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFD9D9D9"/></left>'
            .'<right style="thin"><color rgb="FFD9D9D9"/></right>'
            .'<top style="thin"><color rgb="FFD9D9D9"/></top>'
            .'<bottom style="thin"><color rgb="FFD9D9D9"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1">'
            .'<alignment horizontal="center" vertical="center" wrapText="1"/>'
            .'</xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /** @param  array<int, string>  $sharedStrings */
    protected function sharedStringsXml(array $sharedStrings): string
    {
        $items = '';
        foreach ($sharedStrings as $text) {
            $items .= '<si><t>'.htmlspecialchars($text, ENT_XML1).'</t></si>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
            .$items
            .'</sst>';
    }

    protected function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            .'<Application>USJ E-Learning</Application>'
            .'</Properties>';
    }

    protected function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>USJ E-Learning</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>'
            .'</cp:coreProperties>';
    }
}
