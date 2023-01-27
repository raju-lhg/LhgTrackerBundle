<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\Loader\TimesheetLoader;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface; 
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle; 
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use KimaiPlugin\LhgTrackerBundle\Providers\ServiceProviders\LhgTrackerServiceProvider;
use KimaiPlugin\RecurringBudgetBundle\Entity\BudgetEntry;
use KimaiPlugin\RecurringBudgetBundle\EventSubscriber\ProjectSubscriber; 
use KimaiPlugin\RecurringBudgetBundle\Repository\BudgetRepository;
use Psr\Log\LoggerInterface;
use KimaiPlugin\RecurringBudgetBundle\Utils\Utils; 
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput; 

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
    private $projectRepository;

    public function __construct( 
        TimesheetRepository $time_sheet_repository,
        BudgetRepository $budgetRepository,
        TimesheetService $time_sheet_service,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager, 
        ProjectRepository $projectRepository,
    ) {
        parent::__construct();
        $this->time_sheet_repository = $time_sheet_repository;
        $this->budgetRepository = $budgetRepository;
        $this->time_sheet_service = $time_sheet_service;
        $this->logger = $logger;
        $this->entityManager = $entityManager; 
        $this->projectRepository = $projectRepository;
    }
    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io    = new SymfonyStyle($input, $output);
        $this->io->writeln("<bg=green>********************************** LHGTracker Auto Terminator **********************************************</>");
        $this->io->writeln("<bg=green>============================================================================================================</>");
        $this->terminate_active_records();
        $this->logger->info('Terminator Executed at '. date("Y-m-d h:i:sa"));
        return 0;
    }

    private function terminate_active_records(){
        try {
            // $this->io->writeln("Terminating all active Tracks ...");
            $activeEntries = $this->getActiveEntriesByProject();
            
            $processedProjectIds = [];
            foreach ($activeEntries as $key => $timesheet) {
                
                $project = $timesheet->getProject();
                $isOverBudgetAllowed = $project
                            ->getMetaField(LhgTrackerServiceProvider::CUSTOM_FIELD_NAME);
                if($isOverBudgetAllowed && $isOverBudgetAllowed->getValue() == 1){
                    continue;
                }
                $projectBudget = $this->getProjectBudgetData($project->getId());
                $this->io->writeln(json_encode($projectBudget));
                if(isset($projectBudget)){
                    $spentOnRunningTaskSpent = $this->calculateRunningTasksSpentByProjectId($project);
                    // $this->io->writeln("Cost: ".$spentOnRunningTaskSpent);
                    if($projectBudget['hasRecurringBudget'] == false){
                        $totalBudgetLeft = $projectBudget['budget_left'] - $spentOnRunningTaskSpent;
                        if($totalBudgetLeft >= 0){
                            $this->time_sheet_service->stopTimesheet($timesheet, false);
                        }
                    }
                    else{
                        $interValField = $project
                            ->getMetaField(ProjectSubscriber::RECURRING_BUDGET_INTERVAL_META_FIELD);
                        if($interValField){
                            preg_match_all('!\d+!', $interValField->getValue(), $intervalValue);
                            $nextIntervalField = $project
                            ->getMetaField(ProjectSubscriber::RECURRING_BUDGET_NEXT_INTERVAL_BEGIN_DATE_META_FIELD);
                            if($nextIntervalField){
                                $nextInterValDate = $nextIntervalField->getValue();
                                if($nextInterValDate){
                                    $previousInterValData = Carbon::parse($date)->format('Y-m-d');
                                }
                            }
                        }
                    }
                }
                // if(!in_array($project->getId(), $processedProjectIds)){
                    
                //     array_push($processedProjectIds, $project->getId());
                //     $budgetType           = Utils::getProjectBudgetType($project);
                //     if($budgetType != null){
                //         $this->executeCalculateBudgetCommand($project);

                //         if($budgetType == ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY){ 
                //             $projectBudget          = $project->getBudget();  
                //             $budgetRecurringValue = $project
                //             ->getMetaField(ProjectSubscriber::RECURRING_MONEY_BUDGET_META_FIELD)
                //             ->getValue();

                //             $interValType = $project
                //             ->getMetaField(ProjectSubscriber::RECURRING_BUDGET_INTERVAL_META_FIELD)
                //             ->getValue();

                //             $budgetData             = $this->getBudgetEntryByProject($project);
                //             $spentOnRunningTaskSpent = $this->calculateRunningTasksSpentByProjectId($project);
                //             $this->io->writeln("Project Cost On Running Tasks : ". $spentOnRunningTaskSpent);
                //             if($budgetData){
                //                 $this->io->writeln("budgetData : ". $budgetData->getBudgetAvailable());
                //                 $availableBudgetOnDb    = $budgetData->getBudgetAvailable(); 
                //                 $spentOnRunningTaskSpent = $this->calculateRunningTasksSpentByProjectId($project);
                //                 $this->io->writeln("Project Cost On Running Tasks : ". $spentOnRunningTaskSpent);
                //                 if($availableBudgetOnDb <= $spentOnRunningTaskSpent){
                //                     $this->time_sheet_service->stopTimesheet($timesheet, false);
                //                     $this->executeCalculateBudgetCommand($project);
                //                 }

                //             }

                //         }
                //         else{ 
                            
                //         }
                //     }
                // }
                // $consoleOutput = $project->getId()."# <info>".$timesheet->getUser()->getDisplayName() ." => " . $project->getName(). " => " .  $timesheet->getDescription()."</info>";
                // $this->io->writeln($consoleOutput);
            } 
            
        } catch (\Throwable $th) {
            $this->io->writeln("Exception thrown in ". $th->getFile() ." on line ". $th->getLine().": [Code ".$th->getCode()."]".  $th->getMessage()); 
        }
        
    }

    private function executeCalculateBudgetCommand(Project $project){
        $command = $this->getApplication()->find('recurring-budget:calculate');
        $arguments = [
            '-p'  => $project->getId(),
        ];
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, new BufferedOutput());
    }

    private function calculateRunningTasksSpentByProjectId(Project $project){
        $activeEntries = $this->getActiveEntriesByProject($project);
        $totalSpentOnProject = 0;
        foreach ($activeEntries as $key => $timeSheet) {
            $userHourlyData = $this->getUserHourlyRate($timeSheet->getUser());
            if($userHourlyData){
                $rate = $userHourlyData->getValue();
                $begin = clone $timeSheet->getBegin();
                $end = new DateTime('now', $begin->getTimezone());

                $timeSheet->setBegin($begin);
                $timeSheet->setEnd($end);

                $difference = $end->diff($begin);
                $hours = round($difference->s / 3600 + $difference->i / 60 + $difference->h + $difference->days * 24, 2);

                $spentOnCurrentTask = $rate * $hours;

                $totalSpentOnProject += $spentOnCurrentTask; 

            }
        }

        return $totalSpentOnProject;
    }

    private function getActiveEntriesByProject(Project $project = null)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNotNull('t.begin'))
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        if (null !== $project) {
            $qb->andWhere('t.project = :project');
            $qb->setParameter('project', $project);
        }

        return $this->getHydratedTimeSheetResultsByQuery($qb, false);
    }

    protected function getHydratedTimeSheetResultsByQuery(ORMQueryBuilder $qb, bool $fullyHydrated = false): iterable
    {
        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager(), $fullyHydrated);
        $loader->loadResults($results);

        return $results;
    }

    private function getBudgetEntryByProject(Project $project = null)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('be')
            ->from(BudgetEntry::class, 'be')
            ->andWhere('be.project = :project')
            ->setParameter('project', $project); 
        return $results = $qb->getQuery()->getOneOrNullResult();
    }

    private function getUserHourlyRate(User $user)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('up')
            ->from(UserPreference::class, 'up')
            ->andWhere('up.user = :user')
            ->andWhere('up.name = :name')
            ->setParameter('user', $user)
            ->setParameter('name', 'hourly_rate'); 
        return $results = $qb->getQuery()->getOneOrNullResult();
    }

    private function getProjectBudgetData($projectId){ 
        
        $query = new ProjectQuery();        
        $budgetData     = $this->getBudgetDataForProjectList($query, $projectId);
        $projectIds     = \array_column($budgetData, 'id');
        $projectBudgets = [];
        $returnData       = [];

        foreach ($budgetData as $entry) {
            $project = $this->projectRepository->find($entry['id']);
            // $this->logger->info("Hello");

            if (empty($project)) {
                continue;
            }

            $budgetType           = Utils::getProjectBudgetType($project);
            $hasRecurringBudget   = true;
            $budgetRecurringValue = null;

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

            if (\is_null($entry['time_budget_left']) || \is_null($entry['budget_left'])) {
                switch ($budgetType) {
                    case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_TIME:
                        $entry['time_budget_left'] = $project->getTimeBudget();
                        break;

                    case ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY:
                        $entry['budget'] = $project->getBudget();
                        break;
                }
            }

            $entry['budgetRecurringValue'] = $budgetRecurringValue;
            $entry['budgetType']           = $budgetType;
            $entry['hasRecurringBudget']   = $hasRecurringBudget;
            $entry['total_budget']           = $project->getBudget();

            $projectBudgets[$entry['id']] = $entry; 

            $returnData = $entry;
        }

        return $returnData;
    }

    public function getBudgetDataForProjectList(ProjectQuery $query, $projectId)
    {
        // SELECT
        $sql = "SELECT p.`id`, record.`record_duration`, record.`record_rate`";
        $sql .= ",(p.`time_budget` - record.`record_duration`) as `time_budget_left`";
        $sql .= ",(p.`budget` - record.`record_rate`) as `budget_left`";

        // Sub-selects for recurring budgets
        $sql .= ",(SELECT `value` FROM `kimai2_projects_meta` m WHERE p.`id` = `m`.`project_id` AND";
        $sql .= "`name` = 'Recurring_Time_Budget') as time_budget_recurring";

        $sql .= ",(SELECT `value` FROM `kimai2_projects_meta` m WHERE p.`id` = `m`.`project_id` AND";
        $sql .= "`name` = 'Recurring_Money_Budget') as money_budget_recurring";

        // Sub-selects for teams
        $sql .= ",(SELECT t.`name` FROM `kimai2_teams` t WHERE t.`id` IN (SELECT pt.`team_id` FROM";
        $sql .= "`kimai2_projects_teams` pt WHERE pt.`project_id` = p.`id`) ORDER BY t.`id` ASC LIMIT 1) as project_team";

        $sql .= ",(SELECT t.`name` FROM `kimai2_teams` t WHERE t.`id` IN (SELECT ct.`team_id` FROM";
        $sql .= "`kimai2_customers_teams` ct WHERE ct.`customer_id` = p.`customer_id`) ORDER BY t.`id` ASC LIMIT 1) as customer_team";

        $sql .= " FROM `kimai2_projects` p";
        $sql .= " LEFT JOIN `kimai2_customers` c ON p.`customer_id` = c.`id`";
        $sql .= " LEFT JOIN (SELECT `project_id`, SUM(`duration`) as record_duration, SUM(`rate`) as record_rate";
        $sql .= " FROM `kimai2_timesheet` GROUP BY `project_id`) as record ON p.`id` = record.`project_id`";

        // WHERE
        $where = [
            'p.`visible` = 1',
            'c.`visible` = 1', 
            'p.`id` = '.$projectId
        ];

        if ($query->hasCustomers()) {
            $customerIds = [];

            foreach ($query->getCustomers() as $customer) {
                if (\is_int($customer)) {
                    $customerIds[] = $customer;
                } elseif ($customer instanceof Customer) {
                    $customerIds[] = $customer->getId();
                }
            }

            if (!empty($customerIds)) {
                $where[] = 'p.`customer_id` IN ('.\implode(',', $customerIds).')';
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE ".\implode(' AND ', $where);
        }

        // HAVING
        $having  = [];
        $teamIds = [];

        foreach ($query->getTeams() as $team) {
            $teamIds[] = $team->getName();
        }

        if (!empty($teamIds)) {
            $teamIdsStr = "'".\implode("','", $teamIds)."'";
            $having[]   = '(`project_team` IN ('.$teamIdsStr.') OR `customer_team` IN ('.$teamIdsStr.'))';
        }

        if (!empty($having)) {
            $sql .= " HAVING ".\implode(' AND ', $having);
        } 

        $stmt          = $this->entityManager->getConnection()->prepare($sql);
        $result        = $stmt->executeQuery();
        $projectResult = $result->fetchAllAssociative(); 

        return $projectResult;
    }
}
