<?php

namespace App\Services;

class SimplePdfBuilder
{
    /**
     * Create a simple, valid multi-page PDF document.
     *
     * @return string Raw PDF binary string
     */
    public static function createSamplePdf(string $title, string $author, string $category, string $description): string
    {
        $escapedTitle = self::escapePdfText($title);
        $escapedAuthor = self::escapePdfText($author);
        $escapedCategory = self::escapePdfText($category);
        $escapedDesc = self::escapePdfText($description);

        $pages = [];

        // Page 1: Executive Title Page
        $p1 = "BT /F1 28 Tf 50 720 Td (EXECUTIVE INTELLIGENCE PORTAL) Tj ET\n".
              "BT /F2 12 Tf 50 695 Td (CURATED LEADERSHIP & LIFELONG LEARNING COLLECTION) Tj ET\n".
              "BT /F1 24 Tf 50 620 Td ({$escapedTitle}) Tj ET\n".
              "BT /F2 14 Tf 50 590 Td (Author: {$escapedAuthor}) Tj ET\n".
              "BT /F2 12 Tf 50 565 Td (Discipline: {$escapedCategory}) Tj ET\n".
              "BT /F2 11 Tf 50 500 Td (Executive Overview:) Tj ET\n".
              "BT /F2 10 Tf 50 480 Td ({$escapedDesc}) Tj ET\n".
              "BT /F2 9 Tf 50 100 Td (Confidential Executive Reading Copy - Approved Demonstration Edition 2026) Tj ET\n";
        $pages[] = $p1;

        // Page 2: Chapter 1
        $p2 = "BT /F1 20 Tf 50 720 Td (Chapter 1: Foundational Frameworks) Tj ET\n".
              "BT /F2 12 Tf 50 680 Td (Knowledge becomes actionable when systematic thinking meets decisive execution.) Tj ET\n".
              "BT /F2 10 Tf 50 640 Td (1. Executive Alignment: Ensuring strategy maps to measurable human outcomes.) Tj ET\n".
              "BT /F2 10 Tf 50 610 Td (2. Cognitive Discipline: Overcoming confirmation bias in high-stakes environments.) Tj ET\n".
              "BT /F2 10 Tf 50 580 Td (3. Adaptive Cadence: Maintaining organizational momentum across disruption.) Tj ET\n".
              "BT /F2 11 Tf 50 520 Td (Leadership Principle:) Tj ET\n".
              "BT /F2 10 Tf 50 495 Td (Great institutions are built on relentless curiosity and disciplined reflection.) Tj ET\n".
              "BT /F2 9 Tf 50 100 Td (Executive E-Library - Page 2 of 3) Tj ET\n";
        $pages[] = $p2;

        // Page 3: Chapter 2
        $p3 = "BT /F1 20 Tf 50 720 Td (Chapter 2: Strategic Applications) Tj ET\n".
              "BT /F2 12 Tf 50 680 Td (Translating Executive Insight into Institutional Value) Tj ET\n".
              "BT /F2 10 Tf 50 640 Td (Continuous lifelong learning is the definitive competitive moat of modern leadership.) Tj ET\n".
              "BT /F2 10 Tf 50 610 Td (Review these concepts in your executive debriefs and strategic planning sessions.) Tj ET\n".
              "BT /F2 11 Tf 50 540 Td (End of Demonstration Passage) Tj ET\n".
              "BT /F2 9 Tf 50 100 Td (Executive E-Library - Page 3 of 3) Tj ET\n";
        $pages[] = $p3;

        return self::buildPdfDocument($pages);
    }

    private static function escapePdfText(string $str): string
    {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $str);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $clean);
    }

    private static function buildPdfDocument(array $pageContents): string
    {
        $objects = [];
        $offsets = [];
        $pdf = "%PDF-1.4\n";

        // Object 1: Catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Object 2: Pages list (placeholder, will fill with page refs)
        $pageCount = count($pageContents);
        $pageObjIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjIds[] = (5 + ($i * 2)).' 0 R';
        }
        $kids = implode(' ', $pageObjIds);
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count {$pageCount} >>\nendobj\n";

        // Object 3: Font 1 (Helvetica-Bold)
        $objects[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

        // Object 4: Font 2 (Helvetica)
        $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $currentObj = 5;
        foreach ($pageContents as $content) {
            $contentObjId = $currentObj + 1;
            // Page object
            $objects[$currentObj] = "{$currentObj} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents {$contentObjId} 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>\nendobj\n";
            $currentObj++;

            // Content stream
            $streamLen = strlen($content);
            $objects[$currentObj] = "{$currentObj} 0 obj\n<< /Length {$streamLen} >>\nstream\n{$content}\nendstream\nendobj\n";
            $currentObj++;
        }

        // Build file body and track byte offsets
        foreach ($objects as $id => $objStr) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $objStr;
        }

        // Cross-reference table
        $xrefStart = strlen($pdf);
        $totalObjs = count($objects) + 1;
        $pdf .= "xref\n0 {$totalObjs}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $totalObjs; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        // Trailer
        $pdf .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF\n";

        return $pdf;
    }
}
