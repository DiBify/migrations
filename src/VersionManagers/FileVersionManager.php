<?php
/**
 * Created for LeadVertex
 * Date: 2/10/22 10:53 PM
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\Migrations\Manager\VersionManagers;

use XAKEPEHOK\Path\Path;

class FileVersionManager implements VersionManagerInterface
{

    protected Path $file;
    protected array $applied = [];

    public function __construct(string $filePath = null)
    {
        if ($filePath) {
            $this->file = new Path($filePath);
        } else {
            $this->file = Path::root()->down('migrations')->down('_applied.json');
        }

        if (file_exists((string) $this->file)) {
            $this->applied = json_decode(file_get_contents((string) $this->file), true);
        } else {
            file_put_contents((string) $this->file, '{}');
        }
    }

    public function getApplied(): array
    {
        return $this->applied;
    }

    public function apply(string $name): void
    {
        $this->applied[$name] = date('c');
        $this->save();
    }

    public function rollback(string $name): void
    {
        unset($this->applied[$name]);
        $this->save();
    }

    protected function save(): void
    {
        file_put_contents((string) $this->file, json_encode($this->applied, JSON_PRETTY_PRINT));
    }
}