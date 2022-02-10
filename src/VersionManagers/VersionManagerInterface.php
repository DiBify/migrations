<?php
/**
 * Created for LeadVertex
 * Date: 2/10/22 10:53 PM
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\Migrations\Manager\VersionManagers;

interface VersionManagerInterface
{

    public function getApplied(): array;

    public function apply(string $name): void;

    public function rollback(string $name): void;

}