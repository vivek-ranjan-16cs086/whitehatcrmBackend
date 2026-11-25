<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        // SQLite is used by the test suite. MySQL and MariaDB support native ENUM.
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $pdo = $connection->getPdo();
        $grammar = $connection->getQueryGrammar();
        $changes = [];
        foreach (config('lead-enums') as $field => $definition) {
            // Keep existing custom values so enforcing the enum cannot erase them.
            $saved = $connection->table('leads')->whereNotNull($field)->distinct()->pluck($field)->all();
            $values = array_unique([...$definition['values'], ...$saved]);
            $members = implode(', ', array_map($pdo->quote(...), $values));
            $default = $definition['default'] === null ? 'NULL' : $pdo->quote($definition['default']);
            $changes[] = 'MODIFY COLUMN '.$grammar->wrap($field)." ENUM($members) NULL DEFAULT $default";
        }

        $connection->statement('ALTER TABLE '.$grammar->wrapTable('leads').' '.implode(', ', $changes));
    }

    public function down(): void
    {
        // Keep the deployed enum definitions and preserved values on rollback.
    }
};
