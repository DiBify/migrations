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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use XAKEPEHOK\Path\Path;

class MigrationRunCommand extends Command
{

    private VersionManagerInterface $versionManager;

    public function __construct(VersionManagerInterface $versionManager)
    {
        parent::__construct();
        $this->versionManager = $versionManager;
    }

    public function execute(InputInterface $input, OutputInterface $output)
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

        foreach ($migrations as $name => $filename) {

            if (isset($applied[$name])) {
                continue;
            }

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
                return 0;
            }
        }


        $output->writeln('Executed: ' . $executed);

        return 1;
    }

    protected function configure()
    {
        $this
            ->setName('migration:run')
            ->setDescription('Run .php migration files');
    }

}