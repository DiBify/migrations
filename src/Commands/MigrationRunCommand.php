<?php
/**
 * Created for dibify-migrations
 * Date: 10.02.2022
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\Migrations\Manager\Commands;


use DiBify\Migrations\Manager\VersionManagers\VersionManagerInterface;
use DirectoryIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use XAKEPEHOK\Path\Path;

class MigrationRunCommand extends Command
{

    private VersionManagerInterface $versionManager;

    public function __construct(VersionManagerInterface $versionManager)
    {
        parent::__construct();
        $this->versionManager = $versionManager;
        $this->addOption(
            'yes',
            'y',
            InputOption::VALUE_NONE,
            'Apply new migrations without user confirmation',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $applied = $this->versionManager->getApplied();

        $path = Path::root()->down('migrations');
        $executed = 0;

        $migrations = [];
        foreach (new DirectoryIterator($path) as $fileInfo) {

            if ($fileInfo->getExtension() !== 'php') {
                continue;
            }

            $migrations[$fileInfo->getBasename()] = $fileInfo->getFilename();
        }

        ksort($migrations);

        $migrations = array_filter($migrations, function (string $value, string $key) use ($applied) {
            return !isset($applied[$key]);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($migrations)) {
            $output->writeln('No migrations to apply');
            return Command::SUCCESS;
        }

        if (!$input->getOption('yes')) {
            $output->writeln('Following migrations not applied yet:');
            foreach ($migrations as $name => $filename) {
                $output->writeln(' - ' . $name);
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you want apply this migrations? (y/n) ', false);

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        foreach ($migrations as $name => $filename) {
            $output->writeln("##### START OF EXECUTING `{$name}` #####");
            $process = new Process(['php', $filename], null, $_ENV);
            $process->setTimeout(null);
            $process->setWorkingDirectory($path);
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if ($process->isSuccessful()) {
                $output->writeln("##### FINISHED `{$name}` #####");
                $this->versionManager->apply($name);
                $executed++;
            } else {
                $output->writeln("<error>##### FAILED `{$name}` #####</error>");
                return Command::FAILURE;
            }
        }


        $output->writeln('Executed: ' . $executed);
        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->setName('migration:run')
            ->setDescription('Run .php migration files');
    }

}