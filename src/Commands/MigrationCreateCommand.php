<?php
/**
 * Created for dibify-migrations
 * Date: 10.02.2022
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\Migrations\Manager\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XAKEPEHOK\Path\Path;

class MigrationCreateCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $this->generateName($input->getArgument('name'));

        $content = "<?php\n\n";
        $content.= "//PUT your code here";

        $path = Path::root()->down('migrations');
        if (!is_dir((string) $path)) {
            mkdir((string) $path, 0755, true);
        }
        $filename = "{$path}/{$name}.php";
        file_put_contents($filename, $content);
        chmod($filename, 0755);

        $output->writeln("Migration '{$name}' created");

        return self::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->setName('migration:new')
            ->addArgument('name', InputArgument::REQUIRED)
            ->setDescription('Create new .php migration file');
    }

    private function generateName(string $name): string
    {
        $name = date('YmdHis') . '_' . $name;
        $name = preg_replace('~[^a-zA-Z\d]~', '_', $name);
        return preg_replace('~_+~', '_', $name);
    }

}