<?php

namespace App\Uninter;
interface UninterContractsInterface
{
    public function signInAuthenticated();
    public function getSubjectId(): array|int;
    public function getSubjectLessons(string $nameSubject): array|int;
    public function getSubjectLessonsType(string $nameSubject, string $id): array|int;
    public function getSubject(string $nameSubject): mixed;
    public function getAllTasks(string $nameSubject): array|int;
    public function getAllActivityQuestions(string $taskId): array|int;
    public function insertAlternative(array $alternative): array|int;
    public function confirmationTry(string $cIdAvaliacao, int $try, string $IdAvaliacaoVinculada): array|int;
    public function fetchTopExamGrade(string $cIdAvaliacao): array|int|null;
    public function getLastExamScore(string $cIdAvaliacao): array|int|null;
    public function getArrayExamScores(string $cIdAvaliacao): array;
}
