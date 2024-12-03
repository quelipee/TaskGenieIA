<?php

namespace App\Uninter;

interface UninterProofContractsInterface
{
    public function processExamData(): array;
    public function photoConfirmation(int $id): array;
    public function getAllActivityQuestionsProof(string $idAvaliacaoUsuario, string $token);
}
