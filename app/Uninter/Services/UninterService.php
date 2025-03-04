<?php

namespace App\Uninter\Services;

use App\Models\History;
use App\Uninter\UninterContractsInterface;
use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\TextPart;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Psr\Http\Client\ClientExceptionInterface;
use Random\RandomException;

class UninterService implements UninterContractsInterface
{
    /**
     * @throws ConnectionException
     */
    public function signInAuthenticated(): void
    {
        $response = Http::asForm()->withHeaders([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
        ])->post(env('APP_USER_UNINTER_AUTH'), [
            'login' => env('APP_UNINTER_LOGIN'),
            'senha' => env('APP_UNINTER_PASSWORD'),
        ]);
        $cookies = $response->cookies()->toArray();
        Session::put('uninter_cookies', $cookies);
        $user_info = $response->json()['usuario'];
        Session::put('uninter_user_info', $user_info);
    }

    /**
     * @throws ConnectionException
     */
    public function getSubjectId() : array|int
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $response = Http::withHeaders($headers)
            ->withCookies($cookies, 'uninter.com')
            ->get('https://univirtus.uninter.com/ava/sistema/UsuarioHistoricoCursoOferta/false/Usuario');

        if ($response->successful())
        {
            return $response['usuarioHistoricoCursoOfertas'];
        }else{
            return $response->status();
        }
    }

    /**
     * @throws ConnectionException
     */
    public function getSubjectLessons($nameSubject) : array|int //ver as aulas da materia
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $subject = $this->getSubject($nameSubject);

        $response = Http::withHeaders($headers)
            ->withCookies($cookies, 'uninter.com')
            ->get('https://univirtus.uninter.com/ava/ava/SalaVirtualEstrutura/0/TipoOfertaCriptografado/1?',[
                'id' => urldecode($subject['cId']),
                'idSalaVirtualOferta' => $subject['idSalaVirtualOferta'],
                'idSalaVirtualOfertaAproveitamento' => $subject['idSalaVirtualOfertaAproveitamento']
            ]);

        if ($response->successful())
        {
            return $response->json();
        } else{
            return $response->status();
        }
    }

    /**
     * @throws ConnectionException
     */
    public function getSubjectLessonsType(string $nameSubject, string $id): int|array|null
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $subject = $this->getSubject($nameSubject);
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/ava/salaVirtualAtividade/0/EstruturaOferta/{$subject['idSalaVirtualOferta']}/",[
                'id' => $id,
                'editar' => 'false',
                'idSalaVirtualOfertaPai' => '',
                'idSalaVirtualOfertaAproveitamento' => $subject['idSalaVirtualOfertaAproveitamento']
            ]);

        $response = collect($response['salaVirtualAtividades'])->first(fn($item) => $item['nomeTipoAtividade'] == 'Rota de aprendizagem');

        return $response;
    }
//6978703 pratica
//6978702 teorica
    /**
     * @return array
     */
    public function getHeadersAndCookies(): array
    {
        $cookieArray = Session::get('uninter_cookies');
        $cookies = [];
        foreach ($cookieArray as $cookie) {
            $cookies[$cookie['Name']] = $cookie['Value'];
        }

        $user_info = Session::get('uninter_user_info');
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 OPR/114.0.0.0',
        ];
        foreach ($user_info as $key => $value) {
            if (is_scalar($value)) {
                $headers['X-' . ucfirst($key)] = $value;
            }
        }
        return array($cookies, $headers);
    }

    /**
     * @param $nameSubject
     * @return array|mixed
     * @throws ConnectionException
     */
    public function getSubject($nameSubject): mixed
    {
        $subjects = $this->getSubjectId();
        $subject = [];
        foreach ($subjects as $item) {
            if (strtolower($item['nomeSalaVirtual']) == strtolower($nameSubject)) {
                $subject = $item;
            }
        }
        return $subject;
    }

    /**
     * @throws ConnectionException
     */
    public function getAllTasks($nameSubject) : array|int
    {
        $subject = $this->getSubject($nameSubject);
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $queryParams = http_build_query([
            'numRegistros' => 25,
            'filtro' => '',
            'ordenacao' => '',
            'idSalaVirtual' => $subject['idSalaVirtual'],
            'idSalaVirtualOferta' => $subject['idSalaVirtualOfertaAproveitamento'],
            'ajustarDatasMatriculaCurso' => 'false', // Valor booleano deve ser string
            'cache' => 1732565899977
        ]);
        $response = Http::withHeaders($headers)
            ->withCookies($cookies, 'uninter.com')
            ->get('https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuario/1/paginacao/true?' . $queryParams);
        if ($response->successful())
        {
            return $response->json();
        } else{
            return $response->status();
        }
    }

    /**
     * @throws ConnectionException
     */
    public function getAllActivityQuestions(string $taskId) : array|int
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $response = Http::withHeaders($headers)
            ->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuarioHistorico/{$taskId}/Avaliacao?autorizacao=");
        if ($response->successful())
        {
            return $response['avaliacaoUsuarioHistoricos'];
        }else {
            return $response->status();
        }
    }

    /**
     * @throws ConnectionException
     * @throws ClientExceptionInterface
     */
    public function insertAlternative(array $alternative) : array|int
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $client = new Client(config('services.gemini.access_token'));

        $payload = [];
        $idSala = Session::get('idSala');
        $history = $this->retrieveConversationLog($idSala);

        foreach ($alternative as $key => $value) {
            $formatted = '';
            $labels = ['A', 'B', 'C', 'D', 'E'];
            foreach ($value['alternativas'] as $index => $quest) {
                if(isset($quest['questaoAlternativaAtributos'][0]['valor'])){
                    $valor = $quest['questaoAlternativaAtributos'][0]['valor'];
                }
                 $formatted .= $labels[$index] . ': ' . strip_tags($valor) ."\n";
            }
            $payload = $this->getPayload($value, $formatted, $payload);
            print_r($payload[$key]['alternativas']);
            $textContent = new TextPart('responda essa questao com base no texto, responda somente com o id alternativa correta(ex: A, B, C, D, E):' .
                PHP_EOL . $payload[$key]['questao'] . PHP_EOL . $payload[$key]['comando'] . PHP_EOL . $payload[$key]['alternativas']);

            $result = $client->geminiPro20Flash001()->startChat();
            $response = $result->withHistory($history)->sendMessage($textContent);
            $responseText = strtolower(trim($response->text()));
//            dd($value);
            $idQuestaoAlternativa = $this->getQuestaoAlternativa($responseText, $value['alternativas']);

            $response = Http::withHeaders($headers)
                ->withCookies($cookies, 'uninter.com')
                ->put('https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuarioHistorico/',[
                    'id' => $payload[$key]['id'], // esse é o id da questao
                    'idQuestaoAlternativa' => $idQuestaoAlternativa, // id da alternativa
                    'idAvaliacaoUsuario' => $payload[$key]['idAvaliacaoUsuario'] // id da avaliacao do usuario
                ]);
            print_r($responseText . PHP_EOL);
        }

        if ($response->successful())
        {
            Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuario/{$alternative[0]['idAvaliacaoUsuario']}/Finalizar/1");
            return $response->json();
        } else {
            return $response->status();
        }
    }

    /**
     * @throws ConnectionException
     * @throws RandomException
     */
    public function confirmationTry(string $cIdAvaliacao, int $try, string $IdAvaliacaoVinculada) : array|int
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        $params = http_build_query([
            'ap' => 'false',
            'cIdAvaliacao' => $cIdAvaliacao,
            'idAvaliacaoVinculada' => $IdAvaliacaoVinculada,
            'cache' => random_int(1000000000000, 9999999999999)
        ]);
        $response = Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
            ->get("https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuario/0/tentativa/" . $try . "?" . $params);
        return $response->json();
    }

    public function fetchTopExamGrade(string $cIdAvaliacao) : array|int {
        $response = $this->getAllExamScores($cIdAvaliacao);

        $grade = 0;
        foreach($response['avaliacaoUsuarios'] as $assessment){
            if ($assessment['nota'] != null){
                $grade = $assessment['nota'] >= $grade ? $assessment['nota'] : $grade;
            }else{
                $grade = 0;
            }
        };

        return $grade;
    }

    public function getLastExamScore(string $cIdAvaliacao) : array|int|null {
        $response = $this->getAllExamScores($cIdAvaliacao);

        $grade = [];
        foreach($response['avaliacaoUsuarios'] as $assessment){
            $grade[] = $assessment['nota'];
        };

        return end($grade);
    }

    public function getArrayExamScores(string $cIdAvaliacao) : array{
        $response = $this->getAllExamScores($cIdAvaliacao);
        $payload = [];
        foreach($response['avaliacaoUsuarios'] as $grade_and_try) {
            $payload[] = [
                'try' => $grade_and_try['tentativa'],
                'grade' => $grade_and_try['nota'],
                'cIdAvaliacao' => $grade_and_try['cIdAvaliacao']
            ];
        }
        return $payload;
    }

    private function getAllExamScores(string $cIdAvaliacao): PromiseInterface|Response
    {
        list($cookies, $headers) = $this->getHeadersAndCookies();
        return Http::withHeaders($headers)->withCookies($cookies, 'uninter.com')
        ->get('https://univirtus.uninter.com/ava/bqs/AvaliacaoUsuario/0/UsuariocId?',[
            'cIdAvaliacao' => $cIdAvaliacao
        ]);
    }
    private function retrieveConversationLog(string $idSala): array
    {
        $history = [];
        $entries = History::query()->where('idSala', $idSala)->get();

        foreach ($entries as $historyEntry)
        {
            $roleMap = [
                Role::User->name => Role::User,
                Role::Model->name => Role::Model,
            ];

            if (array_key_exists($historyEntry['role'], $roleMap)) {
                $history[] = Content::text($historyEntry['message'], $roleMap[$historyEntry['role']]);
            }
        }
        return $history;
    }

    /**
     * @param string $responseText
     * @param $alternativas
     * @return int|null
     */
    public function getQuestaoAlternativa(string $responseText, $alternativas) : int | null
    {
        foreach ($alternativas as $key => $alternativa){
            if (isset($alternativa['questaoAlternativaAtributos'][1])){
                $id = $alternativa['id'];
                break;
            }
            if ($key == 4){
                return match ($responseText) {
                    'a' => $alternativas[0]['id'],
                    'b' => $alternativas[1]['id'],
                    'c' => $alternativas[2]['id'],
                    'd' => $alternativas[3]['id'],
                    'e' => $alternativas[4]['id'],
                    default => null,
                };
            }
        }
        return $id;
    }

    /**
     * @param mixed $value
     * @param string $formatted
     * @param array $payload
     * @return array
     */
    public function getPayload(mixed $value, string $formatted, array $payload): array
    {
        $payload[] = [
            'id' => $value['id'],
            'idAvaliacaoUsuario' => $value['idAvaliacaoUsuario'],
            'questao' => $value['questao'],
            'comando' => $value['comando'],
            'alternativas' => $formatted,
        ];
        return $payload;
    }
}
