<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use AppBundle\Service\TableColumnService;

class ListSetupCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('rips:list:setup')
            ->setDescription('Setup presentation of a table')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'Set table')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Restore default columns')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $allTables = $this->getContainer()->getParameter('tables');
        $availableTables = array_keys($allTables);

        // Get the target table from an option or as a fallback from stdin.
        if (!$table = $input->getOption('table')) {
            $tableQuestion = new ChoiceQuestion('Please select a table', $availableTables);
            $table = $helper->ask($input, $output, $tableQuestion);
        }

        if (!in_array($table, $availableTables)) {
            $output->writeln('<error>Failure:</error> Table "' . $table . '" not found');
            return 1;
        }

        $columns = $allTables[$table][TableColumnService::COLUMN_KEY];

        // First check if the user just wants to restore the defaults.
        if ($input->getOption('remove')) {
            $this->getContainer()->get(TableColumnService::class)->removeColumns($table);
            $output->writeln('<info>Success:</info> Removed table "' . $table . '" from config');
            return 0;
        }

        // Print the available columns.
        $columnTable = new Table($output);
        $columnTable->setHeaders(['possible columns']);
        foreach (array_keys($columns) as $column) {
            $columnTable->addRows([[$column]]);
        }
        $columnTable->render();

        // Verify if user really wants to modify the default columns.
        $configureQuestion = new ConfirmationQuestion('Do you want to configure the table now? (y/n) ', false);
        $configureConfirmation = $helper->ask($input, $output, $configureQuestion);
        if (!$configureConfirmation) {
            return 0;
        }

        // Get, clean, and verify the user given columns.
        $columnQuestion = new Question('Please enter the columns you want to select (separated by comma): ');
        $userColumns = explode(',', $helper->ask($input, $output, $columnQuestion));

        foreach ($userColumns as &$userColumn) {
            $userColumn = strtolower(trim($userColumn));

            if (!isset($columns[$userColumn])) {
                $output->writeln('<error>Failure:</error> Column "' . $userColumn . '" not found');
                return 1;
            }
        }

        $userColumns = array_unique($userColumns);

        // Store columns in config.
        $this->getContainer()->get(TableColumnService::class)->storeColumns($table, $userColumns);

        return 0;
    }
}