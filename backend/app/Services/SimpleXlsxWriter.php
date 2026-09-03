<?php

namespace App\Services;

class SimpleXlsxWriter
{
    /**
     * Genera un archivo binario .xlsx moderno (OpenXML) 100% compatible con Microsoft Excel, Google Sheets, WhatsApp y dispositivos móviles.
     * 
     * @param string $sheetName Nombre de la hoja de cálculo
     * @param array $rows Matriz de filas (cada elemento es un array de valores o ['val' => ..., 'style' => 0..3])
     * @return string Contenido binario del archivo .xlsx
     */
    public static function create(string $sheetName, array $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // 4. xl/workbook.xml
        $cleanSheetName = htmlspecialchars(substr($sheetName, 0, 31), ENT_XML1, 'UTF-8');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . $cleanSheetName . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // 5. xl/styles.xml
        // style 0: Normal
        // style 1: Encabezado Azul Institucional (#003B71) texto blanco negrita
        // style 2: Título Sección (#E2E8F0) texto azul negrita
        // style 3: Resaltado KPI Negrita
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FF003B71"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FF003B71"/><name val="Calibri"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF003B71"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
    </border>
  </borders>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $xmlRows = '';
        $rIdx = 1;
        foreach ($rows as $row) {
            $xmlRows .= '<row r="' . $rIdx . '">';
            $cIdx = 0;
            foreach ($row as $cell) {
                $colLetter = self::columnIndexToLetters($cIdx);
                $cellRef = $colLetter . $rIdx;

                $val = is_array($cell) ? ($cell['val'] ?? '') : $cell;
                $style = is_array($cell) ? ($cell['style'] ?? 0) : 0;

                if (is_int($val) || (is_numeric($val) && !is_string($val) && !preg_match('/^0[0-9]/', (string)$val))) {
                    $xmlRows .= '<c r="' . $cellRef . '" s="' . $style . '"><v>' . $val . '</v></c>';
                } else {
                    $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                    $xmlRows .= '<c r="' . $cellRef . '" s="' . $style . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                }
                $cIdx++;
            }
            $xmlRows .= '</row>';
            $rIdx++;
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>' . $xmlRows . '</sheetData>
</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    private static function columnIndexToLetters(int $idx): string
    {
        $letters = '';
        while ($idx >= 0) {
            $letters = chr($idx % 26 + 65) . $letters;
            $idx = intdiv($idx, 26) - 1;
        }
        return $letters;
    }
}
