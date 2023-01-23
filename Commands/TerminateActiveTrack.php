<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\Loader\TimesheetLoader; 
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface; 
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle; 
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
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
            $activeEntries = $this->getActiveEntriesByProject();
            
            $processedProjectIds = [];
            foreach ($activeEntries as $key => $timesheet) {
                $project = $timesheet->getProject();
                if(!in_array($project->getId(), $processedProjectIds)){
                    
                    array_push($processedProjectIds, $project->getId());
                    $budgetType           = Utils::getProjectBudgetType($project);
                    if($budgetType != null){
                        $this->executeCalculateBudgetCommand($project);

                        if($budgetType == ProjectSubscriber::PROJECT_RECURRING_BUDGET_TYPE_MONEY){ 
                            $projectBudget          = $project->getBudget();  
                            $this->io->writeln($projectBudget);                          
                            $budgetData             = $this->getBudgetEntryByProject($project);
                            $this->io->writeln("budgetData : ". $budgetData);
                            if($budgetData){
                                $availableBudgetOnDb    = $budgetData->getBudgetAvailable(); 
                                $spentOnRunningTaskSpent = $this->calculateRunningTasksSpentByProjectId($project);
                                $this->io->writeln("Project Cost On Running Tasks : ". $spentOnRunningTaskSpent);
                                if($availableBudgetOnDb <= $spentOnRunningTaskSpent){
                                    $this->time_sheet_service->stopTimesheet($timesheet, false);
                                    $this->executeCalculateBudgetCommand($project);
                                }

                            }

                        }
                        else{ 
                            //Todo:  Process Time Budget
                        }
                    }
                }
                $consoleOutput = $project->getId()."# <info>".$timesheet->getUser()->getDisplayName() ." => " . $project->getName(). " => " .  $timesheet->getDescription()."</info>";
                $this->io->writeln($consoleOutput);
            }
            // $this->io->writeln($processedProjectIds);
            
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
}
