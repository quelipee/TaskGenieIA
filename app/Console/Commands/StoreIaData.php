<?php

namespace App\Console\Commands;

use App\Uninter\StudyDataExtractorContractsInterface;
use App\Uninter\UninterContractsInterface;
use Illuminate\Console\Command;

class StoreIaData extends Command
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
    protected $signature = 'app:store-ia-data';

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
        $this->studyDataExtractor->prepareSubjectDataForStorage('Fundamentos do Desenvolvimento Mobile');
    }
}
