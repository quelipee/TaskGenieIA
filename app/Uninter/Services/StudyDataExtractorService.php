<?php

namespace App\Uninter\Services;

use App\Uninter\StudyDataExtractorContractsInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

class StudyDataExtractorService implements StudyDataExtractorContractsInterface
{
    public function __construct(
        protected UninterService  $uninterService
    ){}

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function prepareTextForStorage(string $year, string $matter, string $course, string $classRoom): Document | string
    {
        $parser = new Parser();
        list($cookies , $headers) = $this->uninterService->getHeadersAndCookies();

        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://conteudosdigitais.uninter.com/materiais/aulas//gradNova/{$year}/{$course}/{$matter}/{$classRoom}/includes//html/impressao.html");
        if($response->status() != 404){
            return trim(strip_tags(str_replace(["&nbsp;", "&amp;"], " ", $response->body())));
        }else{
            $pdf = $parser->parseFile("https://conteudosdigitais.uninter.com/materiais/aulas//gradNova/{$year}/{$course}/{$matter}/a2/includes//impressao.pdf");
            return $pdf->getText();
        }
    }

    /**
     * @throws ConnectionException
     */
    public function getInfoMatter(string $idAtividade): ?array
    {
        list($cookies , $headers) = $this->uninterService->getHeadersAndCookies();
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/atv/AtividadeItemAprendizagem/{$idAtividade}/Atividade?complementar=false");
        $link = $response['atividadeItemAprendizagens'][0]['itemAprendizagemEtiquetas'][1]['texto'];

        function extractDataFromUrl($link): array|null
        {
            $pattern = '#/([^/]+)/(\d+)/([^/]+)/([^/]+)/#';
//            print_r($link);
            if (preg_match($pattern, $link, $matches)) {
                return [
                    'course' => $matches[3],
                    'year' => $matches[2],
                    'matter' => $matches[4],
                ];
            }
            return null;
        }

        return extractDataFromUrl($link);
    }

    public function getIdAtividade(string $id, string $idSalaVirtualOferta, string $idSalaVirtualOfertaPai) : array
    {
        list($cookies , $headers) = $this->uninterService->getHeadersAndCookies();
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/ava/SalaVirtualAtividade/{$id}/SalaVirtualEstruturaAtividadeDesempenho/{$idSalaVirtualOferta}",[
                'idSalaVirtualOfertaPai' => $idSalaVirtualOfertaPai
            ]);
        return $response['salaVirtualAtividades'];
    }

    /**
     * @throws ConnectionException
     */
    public function prepareSubjectDataForStorage(string $nameSubject): void
    {
        $lessons = $this->uninterService->getSubjectLessons($nameSubject);

        $payload = $this->uninterService->getSubject($nameSubject);
        $idSalaVirtualOferta = $payload['idSalaVirtualOfertaPai'];
        $idSalaVirtualOfertaPai = $payload['idSalaVirtualOfertaAproveitamento'];
        $id = $lessons['salaVirtualEstruturas'][0]['id'];

        $idAtividade = $this->getIdAtividade($id,$idSalaVirtualOferta,$idSalaVirtualOfertaPai);
        $info = $this->getInfoMatter($idAtividade[0]['idAtividade']);
        dd($this->prepareTextForStorage($info['year'],$info['matter'],$info['course'],'a2'));
    }
}
