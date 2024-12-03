<?php

namespace App\Uninter;

interface StudyDataExtractorContractsInterface
{
    public function prepareTextForStorage(string $year, string $matter, string $course,string $classRoom);
    public function prepareSubjectDataForStorage(string $nameSubject);
}
