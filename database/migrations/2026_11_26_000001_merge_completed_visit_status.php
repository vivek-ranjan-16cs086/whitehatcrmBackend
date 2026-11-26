<?php

use App\Services\LeadEnumOptions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();
        $statuses = LeadEnumOptions::parseDefinition($schema->getColumnType('leads', 'visit_status', true));
        $current = $statuses === null ? null : LeadEnumOptions::currentVisitStatuses($statuses);

        if ($current !== null && $current !== $statuses) {
            $column = collect($schema->getColumns('leads'))->firstWhere('name', 'visit_status');
            $grammar = $connection->getQueryGrammar();
            $table = $grammar->wrapTable('leads');
            $collation = $column['collation'] ? ' COLLATE '.$grammar->wrap($column['collation']) : '';
            $quote = $connection->getPdo()->quote(...);
            $setStatuses = function (array $values) use ($connection, $table, $collation, $quote): void {
                $members = implode(', ', array_map($quote, $values));
                $connection->statement("ALTER TABLE $table MODIFY COLUMN visit_status ENUM($members)$collation NULL DEFAULT 'not_scheduled'");
            };

            if (! in_array('visited', $statuses, true)) {
                $setStatuses([...$statuses, 'visited']);
                $current[] = 'visited';
            }
        }

        $connection->table('leads')->whereRaw('LOWER(RTRIM(visit_status)) = ?', ['completed'])
            ->update(['visit_status' => 'visited']);

        if (isset($setStatuses)) {
            $setStatuses($current);
        }
    }

    public function down(): void
    {
        // Both historical values represent the same visit outcome.
    }
};
