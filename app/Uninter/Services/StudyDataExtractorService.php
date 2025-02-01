<?php

namespace App\Uninter\Services;

use App\Models\History;
use App\Uninter\StudyDataExtractorContractsInterface;
use Exception;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
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

        print_r($response->status() . PHP_EOL);
        if (!$response->successful()) {
            try {
                $pdf = $parser->parseFile("https://conteudosdigitais.uninter.com/materiais/aulas//gradNova/{$year}/{$course}/{$matter}/{$classRoom}/includes//impressao.pdf");
                $text = $pdf->getText();
                if (!empty($text)) {
                    return $text;
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            try {
                $pdf = $parser->parseFile("https://conteudosdigitais.uninter.com/materiais/aulas//gradNova/{$year}/{$course}/{$matter}/{$classRoom}/includes//slides.pdf");
                $text = $pdf->getText();
                if (!empty($text)) {
                    return $text;
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            return "Erro ao processar o conteúdo para armazenamento.";
        }

        return trim(strip_tags(str_replace(["&nbsp;", "&amp;"], " ", $response->body())));
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

        return $this->extractDataFromUrl($link);
    }
    private function extractDataFromUrl($link): array|null
    {
        $pattern = '#/([^/]+)/(\d+)/([^/]+)/([^/]+)/#';
        if (preg_match($pattern, $link, $matches)) {
            return [
                'course' => $matches[3],
                'year' => $matches[2],
                'matter' => $matches[4],
            ];
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function insertSubjectContent(string $text, string $matter, $idSala, string $title): void
    {
        $validatedRequest = History::query()->where('title',$title)->exists();
        if($validatedRequest){
           throw new Exception('The title already exists. Please choose a different one.');
        }
        $history = new History([
           'idSala' => $idSala,
           'matter' => $matter,
           'message' => $text,
           'title' => $title,
           'role' => Role::Model->name
        ]);
        $history->save();
    }

    /**
     * @throws ConnectionException
     */
    public function prepareSubjectDataForStorage(string $nameSubject): void
    {
        $lessons = $this->uninterService->getSubjectLessons($nameSubject);
        $i = 1;
        foreach ($lessons['salaVirtualEstruturas'] as $lesson) {
            if (strtolower($lesson['nome']) != 'trabalho' and $i <= 6){
                $id = $lesson['id'];
                $idAtividade = $this->uninterService->getSubjectLessonsType($nameSubject,$id);
                if($idAtividade){
                    $info = $this->getInfoMatter($idAtividade['idAtividade']);
                    $text = $this->prepareTextForStorage($info['year'],$info['matter'],$info['course'],"a".$i++);
                    $this->insertSubjectContent($text, $info['matter'],$idAtividade['idSalaVirtual'], $lesson['nome']);
                }
            }
        }
    }
}
