<?php

namespace App\Console\Commands;

use App\Models\History;
use App\Uninter\StudyDataExtractorContractsInterface;
use App\Uninter\UninterContractsInterface;
use Illuminate\Console\Command;

class StoreIAData extends Command
{
    public function __construct(
        protected UninterContractsInterface $uninterContracts,
        protected StudyDataExtractorContractsInterface $studyDataExtractor,
    )
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->uninterContracts->signInAuthenticated();
        $courses = $this->uninterContracts->getSubjectId();

        foreach ($courses as $course) {
            if ($course['nomeCurso'] === 'CST ANÁLISE E DESENVOLVIMENTO DE SISTEMAS - DISTÂNCIA (2701)' && $this->notCourseInsert($course['nomeSalaVirtual'])){

                print_r($course['nomeSalaVirtual'] . PHP_EOL);
//                "idSalaVirtual" => 92211
//                 "idSalaVirtualOferta" => 715813
                $this->studyDataExtractor->prepareSubjectDataForStorage($course['nomeSalaVirtual']);
            }
        }
    }
    private function notCourseInsert(string $course): string
    {
        return $course != 'Ambientação Inicial' &&
            $course != 'Canal de Comunicação com o Coordenador - ADS/EAD' &&
            $course != 'Atividade Extensionista I: Tecnologia Aplicada à Inclusão Digital – Levantamento';
    }
}
