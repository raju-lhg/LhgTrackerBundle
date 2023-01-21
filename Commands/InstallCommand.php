<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Repository\Query\ProjectQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle; 
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use KimaiPlugin\LhgTrackerBundle\Providers\ServiceProviders\LhgTrackerServiceProvider;
use KimaiPlugin\MetaFieldsBundle\Entity\MetaFieldRule;
use KimaiPlugin\MetaFieldsBundle\Repository\MetaFieldRuleRepository;
use KimaiPlugin\RecurringBudgetBundle\EventSubscriber\ProjectSubscriber;
use KimaiPlugin\RecurringBudgetBundle\Repository\BudgetRepository;
use Psr\Log\LoggerInterface;
use KimaiPlugin\RecurringBudgetBundle\Utils\Utils;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Serializer\Encoder\JsonEncode;

class InstallCommand extends Command
{
    protected static $defaultDescription = 'Install LhgTrackerBundle Plugin.';
    protected static $defaultName = 'lhg-tracker:install';
    protected $io;
    private $time_sheet_repository;
    private $budgetRepository;
    private $time_sheet_service;
    private $logger; 
    private $entityManager;
    // private $metaFieldRuleRepository;

    public function __construct( 
        TimesheetRepository $time_sheet_repository,
        BudgetRepository $budgetRepository,
        TimesheetService $time_sheet_service,
        LoggerInterface $logger, 
        EntityManagerInterface $entityManager,
        // MetaFieldRuleRepository $metaFieldRuleRepository
    ) {
        parent::__construct();
        $this->time_sheet_repository = $time_sheet_repository;
        $this->budgetRepository = $budgetRepository;
        $this->time_sheet_service = $time_sheet_service;
        $this->logger = $logger; 
        $this->entityManager = $entityManager;
        // $this->metaFieldRuleRepository = $metaFieldRuleRepository;
    }
    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io    = new SymfonyStyle($input, $output);
        $this->io->writeln("<bg=green>********************************** Installing LhgTrackerBundle **********************************************</>");
        $this->io->writeln("<bg=green>=============================================================================================================</>");
        if($this->check_if_custom_field_exists()){
                $this->io->writeln(" *************** Meta field already exists *************"); 
        }
        else{
            $this->add_custom_field();
        }
        $this->io->writeln("<bg=green>**************************** Installed LhgTrackerBundle Successfully! ****************************************</>");
        return 0;
    }

    private function check_if_custom_field_exists(): bool{

        try {
            $this->io->writeln(" *************** Checking for Metafields ***************"); 
            $metaFieldEntity = new MetaFieldRule(); 

            $existingField = $this->entityManager->getRepository(get_class($metaFieldEntity))->findOneBy([
                "name" => LhgTrackerServiceProvider::CUSTOM_FIELD_NAME
            ]);
            if($existingField){
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            $this->io->writeln($th->getMessage()); 
        }
        
    }

    private function add_custom_field(): void{
        try {
            $this->io->writeln("********************************** Adding Meta Field **********************************************"); 
            $entity = $this->prepareEntity();
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            
        } catch (\Throwable $th) {
            $this->io->writeln($th->getMessage()); 
        }
        
    }
    private function prepareEntity() :MetaFieldRule
    {
        $entity = new MetaFieldRule();
        $entity->setName(LhgTrackerServiceProvider::CUSTOM_FIELD_NAME);
        $entity->setEntityType(get_class(new Project()));
        $entity->setValue(0);
        $entity->setVisible(1); 
        $entity->setType('boolean');
        $entity->setLabel('Allow Tracking Over Budget');
        $entity->setHelp('Select Yes if you want to allow.');

        return $entity;
    }
}
