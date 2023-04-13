<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface; 
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle; 
use Doctrine\ORM\EntityManagerInterface; 
use KimaiPlugin\LhgTrackerBundle\Providers\ServiceProviders\LhgTrackerServiceProvider;
use KimaiPlugin\MetaFieldsBundle\Entity\MetaFieldRule; 
use Psr\Log\LoggerInterface; 

class ConfigureApproval extends Command
{
    protected static $defaultDescription = 'This command configure database for Timesheet Approval.';
    protected static $defaultName = 'lhg-tracker:approval';
    protected $io;  
    private $logger;
    private $entityManager;  
    private $userReposatory;

    public function __construct(  
        LoggerInterface $logger,
        UserRepository $userReposatory,
        EntityManagerInterface $entityManager,  
    ) {
        parent::__construct();  
        $this->logger = $logger;
        $this->userReposatory = $userReposatory;
        $this->entityManager = $entityManager;  
    }
    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io    = new SymfonyStyle($input, $output);
        $this->io->writeln("<bg=green>**************************** LHGTracker Configuring Approval Plugin ****************************************</>");
        $this->io->writeln("<bg=green>============================================================================================================</>"); 
        $this->io->writeln("<bg=green>============================================================================================================</>"); 
        $this->add_custom_field();
        $this->add_value_to_custom_field();
        // Add default value 8 to 'kimai2_user_preferences' for all users.   
        return 0;
    }

    private function set_first_weekday_for_all_user(User $user): void{
        $preferenceEntity = new UserPreference();
         if($this->check_if_value_to_user_preference_exists($user, $preferenceEntity, "first_weekday")){
            return;
        }

        $preferenceEntity->setUser($user);
        $preferenceEntity->setName("first_weekday");
        $preferenceEntity->setValue("monday");

        $this->entityManager->persist($preferenceEntity);
        $this->entityManager->flush();
    }

    private function add_value_to_custom_field(): void{
        $userEntity = new User(); 
        $allUsers = $this->entityManager->getRepository(get_class($userEntity))->findAll(); 

        foreach ($allUsers as $key => $user) {
            $this->set_value_to_user_preference($user);
            $this->set_first_weekday_for_all_user($user);
        }
    }

    private function set_value_to_user_preference(User $user): void{
        $preferenceEntity = new UserPreference(); 
        if($this->check_if_value_to_user_preference_exists($user, $preferenceEntity, LhgTrackerServiceProvider::DAILY_WORK_TIMECUSTOM_FIELD_NAME)){
            return;
        }
        
        $preferenceEntity->setUser($user);
        $preferenceEntity->setName(LhgTrackerServiceProvider::DAILY_WORK_TIMECUSTOM_FIELD_NAME);
        $preferenceEntity->setValue(LhgTrackerServiceProvider::DAILY_WORK_TIMECUSTOM_FIELD_VALUE);

        $this->entityManager->persist($preferenceEntity);
        $this->entityManager->flush();
    }

    private function check_if_value_to_user_preference_exists(User $user, UserPreference $entity, string $name): bool{
        $existingField = $this->entityManager->getRepository(get_class($entity))->findOneBy([
            "name" => $name, 
            "user" => $user
        ]);
        if($existingField){
            return true;
        }
        return false;
    }
    
    private function check_if_custom_field_exists(): bool{

        try {
            $this->io->writeln(" *************** Checking for Metafields ***************"); 
            $metaFieldEntity = new MetaFieldRule(); 

            $existingField = $this->entityManager->getRepository(get_class($metaFieldEntity))->findOneBy([
                "name" => LhgTrackerServiceProvider::DAILY_WORK_TIMECUSTOM_FIELD_NAME
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

            if($this->check_if_custom_field_exists()){
                $this->io->writeln(" *************** Meta field already exists *************"); 
                return;
            }
            $this->io->writeln("*************** Adding Meta Field ***************"); 
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
        $entity->setName(LhgTrackerServiceProvider::DAILY_WORK_TIMECUSTOM_FIELD_NAME);
        $entity->setEntityType(get_class(new User()));
        $entity->setValue(8);
        $entity->setVisible(1); 
        $entity->setType('number');
        $entity->setLabel('Daily Worke Time Limit');
        $entity->setHelp('Insert maximum allowed work time for this user');

        return $entity;
    }
}
