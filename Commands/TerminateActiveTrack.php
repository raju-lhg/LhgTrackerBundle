<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Utils\LocaleFormatter;
use KimaiPlugin\RecurringBudgetBundle\EventSubscriber\ProjectSubscriber;
use Psr\Log\LoggerInterface;
use KimaiPlugin\RecurringBudgetBundle\Utils\Utils;
use Symfony\Component\Serializer\Encoder\JsonEncode;

class TerminateActiveTrack extends Command
{
    protected static $defaultDescription = 'Terminates all active records that exceed allocated budget.';
    protected static $defaultName = 'lhg-tracker:auto-terminate';
    protected $io;
    private $time_sheet_repository;
    private $time_sheet_service;
    private $logger;

    public function __construct( 
        TimesheetRepository $time_sheet_repository,
        TimesheetService $time_sheet_service,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->time_sheet_repository = $time_sheet_repository;
        $this->time_sheet_service = $time_sheet_service;
        $this->logger = $logger;
    }
    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io    = new SymfonyStyle($input, $output);
        $this->io->writeln("<bg=green>********************************** LHGTracker Auto Terminator **********************************************</>");
        $this->io->writeln("<bg=green>============================================================================================================</>");
        $this->terminate_active_records();
        return 0;
    }

    private function terminate_active_records(){
        $this->io->writeln("Terminating all active Tracks ...");
        $activeEntries = $this->time_sheet_repository->getActiveEntries();
        $index = 0;
        foreach ($activeEntries as $key => $timesheet) {
            $index++;
            $consoleOutput = $timesheet->getProject()->getId()."# <info>".$timesheet->getUser()->getDisplayName() ." => " . $timesheet->getProject()->getName(). " => " .  $timesheet->getDescription()."</info>";
            $this->io->writeln($consoleOutput); 
            // Checks if project has Budget type set. 
            // if(Utils::getProjectBudgetType($timesheet->getProject())){
            //     // ToDo: 
            //     // get ProjectBudgetInterval
            //     $projectBudgetInterval = Utils::getProjectBudgetInterval($timesheet->getProject());
            //     // get ProjectBudgetNextIntervalBeginDate
            //     $ProjectBudgetNextIntervalBeginDate = Utils::getProjectBudgetNextIntervalBeginDate($timesheet->getProject());
            //     // get CalculateTotalTimeAndAmountTrackedInCurrentInterval
            //     // Terminate If Budget Reached or Exceeds
            //     // $this->time_sheet_service->stopTimesheet($timesheet);
            // }
            $project = $timesheet->getProject();
            $budgetType           = Utils::getProjectBudgetType($project);
            $hasRecurringBudget   = true;
            $budgetRecurringValue = null;
            $entry = [];

            if (!$budgetType) {
                $hasRecurringBudget = false;

                if ($project->getBudget() > 0) {
                    $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY;
                } elseif ($project->getTimeBudget() > 0) {
                    $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME;
                } elseif ((int)$entry['time_budget_left'] !== 0) {
                    $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME;
                } elseif ((int)$entry['budget_left'] !== 0) {
                    $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY;
                }
            } else {
                switch ($budgetType) {
                    case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME:
                        $budgetRecurringValue = $project
                            ->getMetaField(ProjectSubscriber::RECURRING_TIME_BUDGET_META_FIELD)
                            ->getValue();

                        $budgetRecurringValue = Utils::convertDurationStringToSeconds($budgetRecurringValue);
                        break;

                    case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY:
                        $budgetRecurringValue = $project
                            ->getMetaField(ProjectSubscriber::RECURRING_MONEY_BUDGET_META_FIELD)
                            ->getValue();
                        break;
                }
            }
            $entry['budgetRecurringValue'] = $budgetRecurringValue;
            $entry['budgetType']           = $budgetType;
            $entry['hasRecurringBudget']   = $hasRecurringBudget;

            $this->io->writeln(json_encode($entry)); 
            
        }
        
    }
}
