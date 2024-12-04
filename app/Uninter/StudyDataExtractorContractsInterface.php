<?php

namespace App\Uninter;

use Smalot\PdfParser\Document;

interface StudyDataExtractorContractsInterface
{
    public function prepareTextForStorage(string $year, string $matter, string $course,string $classRoom): Document | string;
    public function prepareSubjectDataForStorage(string $nameSubject): void;
}
