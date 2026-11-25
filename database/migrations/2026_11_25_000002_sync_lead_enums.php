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
        $columns = array_column($connection->getSchemaBuilder()->getColumns('leads'), null, 'name');
        $changes = [];
        foreach (config('lead-enums') as $field => $definition) {
            $column = $grammar->wrap($field);
            $collation = $columns[$field]['collation'];
            $collationClause = $collation ? ' COLLATE '.$grammar->wrap($collation) : '';

            // Compare using the destination collation and the trailing-space handling of ENUM.
            $member = "RTRIM($column)$collationClause";
            $saved = $connection->table('leads')
                ->selectRaw("$member AS enum_value")
                ->whereNotNull($field)
                ->whereNotIn($connection->raw($member), $definition['values'])
                ->distinct()
                ->pluck('enum_value')
                ->all();
            $values = [...$definition['values'], ...$saved];
            $members = implode(', ', array_map($pdo->quote(...), $values));
            $default = $definition['default'] === null ? 'NULL' : $pdo->quote($definition['default']);
            $changes[] = "MODIFY COLUMN $column ENUM($members)$collationClause NULL DEFAULT $default";
        }

        $connection->statement('ALTER TABLE '.$grammar->wrapTable('leads').' '.implode(', ', $changes));
    }

    public function down(): void
    {
        // Keep the deployed enum definitions and preserved values on rollback.
    }
};
