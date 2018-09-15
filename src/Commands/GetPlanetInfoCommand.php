<?php
/**
 * Created by PhpStorm.
 * User: tio
 * Date: 2018-09-15
 * Time: 16:29
 */

namespace Console\Commands;


use Console\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetPlanetInfoCommand extends Command
{

    /**
     * Setups the command data
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:parse-planet-info')

            // the short description shown while running "php bin/console list"
            ->setDescription('Parses the planet info')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Parses the planet info')
            ->addOption('planet', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Planets names to get', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $planetNames = $input->getOption('planet');

        if ( count($planetNames) == 0 ) {
            $output->writeln('No planets passed');
        }

        foreach ( $planetNames as $planetName ) {
            $output->writeln('Get ' . $planetName . ' info.');
            $parser = new Parser($planetName);
            $parser->getPlanetData();
        }
    }

}