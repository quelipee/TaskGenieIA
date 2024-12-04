<?php

namespace App\Console\Commands;

use App\Uninter\UninterContractsInterface;
use App\Uninter\UninterProofContractsInterface;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Session;
use JetBrains\PhpStorm\NoReturn;

class Uninter extends Command
{
    public function __construct(
        protected UninterContractsInterface $uninterContracts,
        protected UninterProofContractsInterface $uninterProofContracts,
        protected string $disable = 'sim',
    )
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:uninter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    #[NoReturn] public function handle() : void
    {
        $name_courses = [];
        $this->uninterContracts->signInAuthenticated();
        $choice = $this->choice('deseja realizar uma prova ou apol?',['prova','apol']);
        match ($choice){
            'prova' => $this->getExamPerformance(),
            'apol' => $this->submitApol($name_courses)
        };
    }

    /**
     * @return string
     */
    public function signOut() : string
    {
        return $this->choice('Deseja continuar?', ['sim', 'nao']);
    }
    private function processActivity ($cIdAvaliacao, $try, $IdAvaliacaoVinculada, $nomeClassificacao) : void {
        if (strtolower($nomeClassificacao) == 'simulado' || strtolower($nomeClassificacao) == 'apol objetiva' || strtolower($nomeClassificacao) == 'objetiva'
         || strtolower($nomeClassificacao) == 'prova atividade prática'){
            $idTry = $this->uninterContracts->confirmationTry(urldecode($cIdAvaliacao), $try, urldecode($IdAvaliacaoVinculada)); // pegando o id pela tentativa, somente funciona se for no curso de ingles
            $alternative = $this->uninterContracts->getAllActivityQuestions($idTry['avaliacaoUsuario']['id']);
            $this->uninterContracts->insertAlternative($alternative);
        }elseif (strtolower($nomeClassificacao) == 'prova objetiva' || strtolower($nomeClassificacao) == 'prova'){
            $payload = $this->uninterContracts->confirmationTry(urldecode($cIdAvaliacao),$try,urldecode($IdAvaliacaoVinculada));
            $avaliacaoUsuarioToken = $this->uninterProofContracts->photoConfirmation($payload['avaliacaoUsuario']['id']);
            $alternative = $this->uninterProofContracts->getAllActivityQuestionsProof($avaliacaoUsuarioToken['idAvaliacaoUsuario'],$avaliacaoUsuarioToken['token']);
            $this->uninterContracts->insertAlternative($alternative['avaliacaoUsuarioHistoricos']);
        }
    }

    private function processAndLogGrade($cIdAvaliacao, $try, $IdAvaliacaoVinculada, $nomeClassificacao): void
    {
        $this->processActivity($cIdAvaliacao, $try, $IdAvaliacaoVinculada, $nomeClassificacao);
        $last_grade = $this->uninterContracts->getArrayExamScores($cIdAvaliacao);
        $first_grade = array_column(
            array_filter($last_grade, fn($item) => $item['try'] == $try),
            'grade'
        )[0] ?? null;

        $this->info('Nota da atividade: ' . ($first_grade ?? 'Não encontrada'));
    }

    /**
     * @param Closure|array $collection_task
     * @return void
     */
    public function processTask(Closure|array $collection_task): void
    {
        $IdAvaliacaoVinculada = $collection_task['cIdAvaliacaoVinculada'];
        $cIdAvaliacao = $collection_task['cIdAvaliacao'];
        $try = $collection_task['tentativa'];
        $highest_grade = $this->uninterContracts->fetchTopExamGrade($cIdAvaliacao);
        $try_and_grade = $this->uninterContracts->getArrayExamScores($cIdAvaliacao);
        $nomeClassificacao = $collection_task['avaliacao']['nomeAvaliacaoTipo'];

        $this->handleGradesNull($try_and_grade, $collection_task['tentativaTotal'], $highest_grade, $IdAvaliacaoVinculada,$nomeClassificacao);

        $this->handleGrades($collection_task, $try, $highest_grade, $cIdAvaliacao, $IdAvaliacaoVinculada, $nomeClassificacao);
    }

    /**
     * @param array $try_and_grade
     * @param $tentativaTotal
     * @param int|array|null $highest_grade
     * @param mixed $IdAvaliacaoVinculada
     * @param string $nomeClassificacao
     * @return void
     */
    public function handleGradesNull(array $try_and_grade, $tentativaTotal, int|array|null $highest_grade, mixed $IdAvaliacaoVinculada, string $nomeClassificacao): void
    {
        foreach ($try_and_grade as $grade) {
            if ($grade['try'] == $tentativaTotal) {
                $this->info('Voce tem somente 1 tentativa. | Maior Nota: ' . $highest_grade);
                if($this->signOut() == 'nao'){
                    return;
                };
            }
            if ($grade['grade'] === null) {
                $this->processAndLogGrade($grade['cIdAvaliacao'], $grade['try'], $IdAvaliacaoVinculada,$nomeClassificacao);
                return;
            };
        }
    }

    /**
     * @param Closure|array $collection_task
     * @param mixed $try
     * @param int|array|null $highest_grade
     * @param mixed $cIdAvaliacao
     * @param mixed $IdAvaliacaoVinculada
     * @param string $nomeClassificacao
     * @return void
     */
    public function handleGrades(Closure|array $collection_task, mixed $try, int|array|null $highest_grade, mixed $cIdAvaliacao, mixed $IdAvaliacaoVinculada, string $nomeClassificacao): void
    {
            if ($collection_task['tentativaTotal'] - $try == 1) {
                $this->info('Voce tem somente 1 tentativa. | Maior Nota: ' . $highest_grade);
                if($this->signOut() == 'nao'){
                    return;
                };
            }

            if ($collection_task['nota'] >= 70) {
                $this->info('Atualmente sua nota esta acima de 70, NOTA: ' . $highest_grade);
                if($this->signOut() == 'nao'){
                    return;
                };
            }

            if ($try == 1 && $collection_task['nota'] === null) {
                $this->processAndLogGrade($cIdAvaliacao, $try, $IdAvaliacaoVinculada,$nomeClassificacao);
            } elseif($try >= $collection_task['tentativaTotal']) {
                    $this->info('Atingiu o maximo de tentativas nessa atividade: ' . $collection_task['tentativaTotal']);
            } else{
                $this->processAndLogGrade($cIdAvaliacao, $try + 1, $IdAvaliacaoVinculada,$nomeClassificacao);
            }
    }

    /**
     * @param array $name_courses
     * @return void
     */
    public function submitApol(array $name_courses): void
    {
        $courses = $this->uninterContracts->getSubjectId();
        foreach ($courses as $course) {
            $name_courses[] = $course['nomeSalaVirtual'];
        }
        while ($this->disable == 'sim') {
            $name_activity = [];
            $matter = $this->choice('Qual matéria deseja?', $name_courses);
            $task = $this->uninterContracts->getAllTasks(strtoupper($matter));

            foreach ($task['avaliacaoUsuarios'] as $nameTask) {
                $name_activity[] = $nameTask['avaliacao']['nome'];
            };

            $name = $this->choice('Qual atividade deseja?', $name_activity);
            $collection_task = collect($task['avaliacaoUsuarios'])->first(fn($item) => $item['avaliacao']['nome'] == $name);
            Session::put('idSala',$task['avaliacaoUsuarios'][0]['salas'][0]['idSalaVirtual']);
            $this->processTask($collection_task);

            if ($this->signOut() === 'nao') {
                $this->info('Você escolheu sair.');
                break;
            }
        }
    }

    public function getExamPerformance(): void
    {
        $name = [];
        $matter = $this->uninterProofContracts->processExamData();
        foreach ($matter as $nameTask) {
            $name[strtolower($nameTask['salas'][0]['nomeSalaVirtual'])] = $nameTask['avaliacao']['nome'];
        }

        while ($this->disable == 'sim') {
            $proof = $this->choice('qual prova deseja?', $name);
            $collection_proof = collect($matter)->first(fn($item) => strtolower($item['salas'][0]['nomeSalaVirtual']) == $proof);

            $this->processTask($collection_proof);

            if ($this->signOut() === 'nao') {
                $this->info('Você escolheu sair.');
                break;
            }
        }
    }
}
