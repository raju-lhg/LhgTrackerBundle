<?php

namespace KimaiPlugin\LhgTrackerBundle\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Utils\LocaleFormatter;

class TerminateActiveTrack extends Command
{
    protected static $defaultDescription = 'Terminates all active records that exceed allocated budget.';
    protected static $defaultName = 'lhg-tracker:auto-terminate';
    protected $io;
    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io    = new SymfonyStyle($input, $output);
        $this->io->writeln("Hello from LHGTracker Auto Terminate Command");
        $this->terminate_active_records();
        return 0;
    }

    private function terminate_active_records(){
        $this->io->writeln("Terminating all active Tracks ...");
    }
}
