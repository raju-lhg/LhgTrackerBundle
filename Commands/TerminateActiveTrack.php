<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Repository\Query\ProjectQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Utils\LocaleFormatter;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\RecurringBudgetBundle\Entity\BudgetEntry;
use KimaiPlugin\RecurringBudgetBundle\EventSubscriber\ProjectSubscriber;
use KimaiPlugin\RecurringBudgetBundle\Repository\BudgetRepository;
use Psr\Log\LoggerInterface;
use KimaiPlugin\RecurringBudgetBundle\Utils\Utils;
use PhpCsFixer\Console\Output\NullOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;

class TerminateActiveTrack extends Command
{
    protected static $defaultDescription = 'Terminates all active records that exceed allocated budget.';
    protected static $defaultName = 'lhg-tracker:terminate';
    protected $io;
    private $time_sheet_repository;
    private $budgetRepository;
    private $time_sheet_service;
    private $logger;
    private $entityManager; 

    public function __construct( 
        TimesheetRepository $time_sheet_repository,
        BudgetRepository $budgetRepository,
        TimesheetService $time_sheet_service,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager, 
    ) {
        parent::__construct();
        $this->time_sheet_repository = $time_sheet_repository;
        $this->budgetRepository = $budgetRepository;
        $this->time_sheet_service = $time_sheet_service;
        $this->logger = $logger;
        $this->entityManager = $entityManager; 
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
        try {
            $this->io->writeln("Terminating all active Tracks ...");
            $activeEntries = $this->time_sheet_repository->getActiveEntries();
            $processedProjectIds = [];
            foreach ($activeEntries as $key => $timesheet) {
                $project = $timesheet->getProject();
                if(!in_array($project->getId(), $processedProjectIds)){
                    
                    array_push($processedProjectIds, $project->getId());
                    $budgetType           = Utils::getProjectBudgetType($project);
                    if($budgetType != null){
                        if($budgetType == ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY){
                            $command = $this->getApplication()->find('recurring-budget:calculate');
                            $arguments = [
                                '-p'  => $project->getId(),
                            ];
                            $greetInput = new ArrayInput($arguments);
                            $command->run($greetInput, new BufferedOutput()); 
                        }
                        else{
                            $this->io->writeln("Time Time");
                        }
                    }
                }
                $consoleOutput = $project->getId()."# <info>".$timesheet->getUser()->getDisplayName() ." => " . $project->getName(). " => " .  $timesheet->getDescription()."</info>";
                $this->io->writeln($consoleOutput);
            }
            // $this->io->writeln($processedProjectIds);
            
        } catch (\Throwable $th) {
            $this->io->writeln($th->getMessage()); 
        }
        
    }

    // private function terminate_active_records(){
    //     try {
    //         $this->io->writeln("Terminating all active Tracks ...");
    //         $activeEntries = $this->time_sheet_repository->getActiveEntries();
    //         $activeProjectIds = [];
    //         foreach ($activeEntries as $key =>  $timeEntry) {
    //             array_push($activeProjectIds, $timeEntry->getProject()->getId());
    //         }
    //         $this->io->writeln(json_encode($activeProjectIds)); 
    //         $index = 0;
    //         foreach ($activeEntries as $key => $timesheet) {
    //             $index++;
    //             $consoleOutput = $timesheet->getProject()->getId()."# <info>".$timesheet->getUser()->getDisplayName() ." => " . $timesheet->getProject()->getName(). " => " .  $timesheet->getDescription()."</info>";
    //             $this->io->writeln($consoleOutput); 

    //             $query = new ProjectQuery();  
    //             $budgetData     = $this->budgetRepository->getBudgetDataForProjectList($query); 
    //             $projectIds     = \array_column($budgetData, 'id');
    //             $projectBudgets = [];
    //             $projects       = [];

    //             foreach ($budgetData as $entry) {
    //                 // $this->io->writeln(json_encode($entry));
    //                 if(in_array($entry['id'], $activeProjectIds)){
    //                     $project = $timesheet->getProject();

    //                     if (empty($project)) {
    //                         continue;
    //                     }

    //                     $budgetType           = Utils::getProjectBudgetType($project);
    //                     $hasRecurringBudget   = true;
    //                     $budgetRecurringValue = null;

    //                     if (!$budgetType) {
    //                         $hasRecurringBudget = false;

    //                         if ($project->getBudget() > 0) {
    //                             $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY;
    //                         } elseif ($project->getTimeBudget() > 0) {
    //                             $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME;
    //                         } elseif ((int)$entry['time_budget_left'] !== 0) {
    //                             $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME;
    //                         } elseif ((int)$entry['budget_left'] !== 0) {
    //                             $budgetType = ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY;
    //                         }
    //                     } else {
    //                         switch ($budgetType) {
    //                             case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME:
    //                                 $budgetRecurringValue = $project
    //                                     ->getMetaField(ProjectSubscriber::RECURRING_TIME_BUDGET_META_FIELD)
    //                                     ->getValue();

    //                                 $budgetRecurringValue = Utils::convertDurationStringToSeconds($budgetRecurringValue);
    //                                 break;

    //                             case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY:
    //                                 $budgetRecurringValue = $project
    //                                     ->getMetaField(ProjectSubscriber::RECURRING_MONEY_BUDGET_META_FIELD)
    //                                     ->getValue();
    //                                 break;
    //                         }
    //                     }

    //                     if (\is_null($entry['time_budget_left']) || \is_null($entry['budget_left'])) {
    //                         switch ($budgetType) {
    //                             case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME:
    //                                 $entry['time_budget_left'] = $project->getTimeBudget();
    //                                 break;

    //                             case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY:
    //                                 $entry['budget'] = $project->getBudget();
    //                                 break;
    //                         }
    //                     }

    //                     $entry['budgetRecurringValue'] = $budgetRecurringValue;
    //                     $entry['budgetType']           = $budgetType;
    //                     $entry['hasRecurringBudget']   = $hasRecurringBudget;

    //                     $projectBudgets[$entry['id']] = $entry;
    //                     $projects[]                   = $project;
    //                     // Terminate The Tracker Record
    //                     if((int) $entry['time_budget_left'] <=0 || (int) $entry['budget_left'] <=0){
    //                         // $this->time_sheet_service->stopTimesheet($timesheet); 
    //                     }
    //                     $this->io->writeln("Following Entry Stopped"); 
    //                     $this->io->writeln(json_encode($entry)); 
    //                 }
    //                 else{
    //                     // $this->io->writeln("No active record for this project");
    //                 }
    //             }
                
    //         }
    //     } catch (\Throwable $th) {
    //         $this->io->writeln($th->getMessage()); 
    //     }
        
    // }
}
