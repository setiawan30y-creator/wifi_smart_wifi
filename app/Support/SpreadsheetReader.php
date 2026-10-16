<?php

namespace App\Support;

/**
 * Pembaca file import (CSV/TXT, XLS hasil export sistem / SpreadsheetML, dan XLSX)
 * tanpa library tambahan. Dipakai oleh import Pelanggan dan import Invoice.
 *
 * Semua method mengembalikan array baris, tiap baris berupa array nilai sel (string).
 * Jika file tidak bisa dibaca, hasilnya array kosong.
 */
class SpreadsheetReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_SS = 'urn:schemas-microsoft-com:office:spreadsheet';

    /**
     * Baca file berdasarkan ekstensi: csv, txt, xls, xlsx.
     */
    public static function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'csv', 'txt' => self::csv($path),
            'xls' => self::spreadsheetMl($path),
            'xlsx' => self::xlsx($path),
            default => [],
        };
    }

    /**
     * CSV/TXT dengan deteksi otomatis pemisah ";" atau ",".
     */
    public static function csv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle) ?: '';
        rewind($handle);

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * File .xls berformat XML Spreadsheet 2003 (format yang dihasilkan fitur export sistem ini).
     */
    public static function spreadsheetMl(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $rows = [];

        try {
            $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$xml) {
                return [];
            }

            $xml->registerXPathNamespace('ss', self::NS_SS);

            foreach ($xml->xpath('//ss:Row') ?: [] as $xmlRow) {
                $cells = [];
                $currentIndex = 1;

                foreach ($xmlRow->children(self::NS_SS)->Cell as $cell) {
                    $attributes = $cell->attributes(self::NS_SS);
                    if (isset($attributes['Index'])) {
                        $currentIndex = (int) $attributes['Index'];
                    }

                    while (count($cells) < $currentIndex - 1) {
                        $cells[] = '';
                    }

                    $cells[] = (string) $cell->children(self::NS_SS)->Data;
                    $currentIndex++;
                }

                $rows[] = $cells;
            }
        } catch (\Throwable) {
            return [];
        }

        return $rows;
    }

    /**
     * File .xlsx (Excel modern). Hanya sheet pertama yang dibaca.
     */
    public static function xlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        try {
            $sharedStrings = self::xlsxSharedStrings($zip);
            $sheetPath = self::firstXlsxSheetPath($zip);
            $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;

            if ($sheetXml === false) {
                return [];
            }

            $xml = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$xml) {
                return [];
            }

            $xml->registerXPathNamespace('main', self::NS_MAIN);
            $rows = [];

            foreach ($xml->xpath('//main:sheetData/main:row') ?: [] as $xmlRow) {
                $cells = [];

                foreach ($xmlRow->children(self::NS_MAIN)->c as $cell) {
                    $attributes = $cell->attributes();
                    $cellRef = (string) ($attributes['r'] ?? '');
                    $type = (string) ($attributes['t'] ?? '');
                    $columnIndex = $cellRef ? self::xlsxColumnIndex($cellRef) : count($cells);

                    while (count($cells) < $columnIndex) {
                        $cells[] = '';
                    }

                    if ($type === 's') {
                        $value = $sharedStrings[(int) ($cell->v ?? 0)] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) ($cell->is->t ?? '');
                    } else {
                        $value = (string) ($cell->v ?? '');
                    }

                    $cells[$columnIndex] = $value;
                }

                $rows[] = $cells;
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private static function xlsxSharedStrings(\ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$xml) {
            return [];
        }

        $strings = [];
        foreach ($xml->children(self::NS_MAIN)->si as $si) {
            $parts = [];
            foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private static function firstXlsxSheetPath(\ZipArchive $zip): ?string
    {
        $fallback = $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook === false || $rels === false) {
            return $fallback;
        }

        $workbookXml = @simplexml_load_string($workbook, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        $relsXml = @simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);

        if (!$workbookXml || !$relsXml) {
            return $fallback;
        }

        $workbookXml->registerXPathNamespace('main', self::NS_MAIN);
        $sheets = $workbookXml->xpath('//main:sheets/main:sheet') ?: [];

        if (empty($sheets)) {
            return $fallback;
        }

        $relationshipId = (string) ($sheets[0]->attributes(self::NS_REL)['id'] ?? '');

        foreach ($relsXml->Relationship as $relationship) {
            $attributes = $relationship->attributes();
            if ((string) $attributes['Id'] === $relationshipId) {
                $target = ltrim((string) $attributes['Target'], '/');
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }

        return $fallback;
    }

    private static function xlsxColumnIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/i', $cellRef, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
