<?php

namespace App\Uninter\Services;

use App\Uninter\UninterContractsInterface;
use App\Uninter\UninterProofContractsInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Random\RandomException;

class UninterServiceProof implements UninterProofContractsInterface
{
    public function __construct(
        protected UninterContractsInterface $service
    ){}

    /**
     * @throws RandomException
     * @throws ConnectionException
     */
    public function processExamData() : array
    {
        list($cookies, $headers) = $this->service->getHeadersAndCookies();
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get('https://univirtus.uninter.com/ava/bqs//avaliacaoUsuario/false/vigenteProvas',[
                'cache' => random_int(1000000000000, 9999999999999)
            ]);
        return $response['avaliacaoUsuarios'];
    }

    /**
     * @throws ConnectionException
     */
    public function photoConfirmation(int $id): array
    {
        list($cookies, $headers) = $this->service->getHeadersAndCookies();
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->post('https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuarioReconhecimento/',[
                'id' => $id,
                'rkg' => fake()->imageUrl(),
            ]);
        return $response['avaliacaoUsuarioToken'];
    }

    /**
     * @throws ConnectionException
     */
    public function getAllActivityQuestionsProof(string $idAvaliacaoUsuario, string $token)
    {
        list($cookies, $headers) = $this->service->getHeadersAndCookies();
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuarioHistorico/{$idAvaliacaoUsuario}/Token/{$token}");
        return $response->json();
    }
}
//https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuarioHistorico/123967036/Token/7878
