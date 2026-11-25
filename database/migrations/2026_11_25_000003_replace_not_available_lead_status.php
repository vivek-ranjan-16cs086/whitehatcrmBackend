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
        $statuses = LeadEnumOptions::parseDefinition($schema->getColumnType('leads', 'status', true));
        $current = $statuses === null ? null : LeadEnumOptions::currentStatuses($statuses);

        if ($current !== null && $current !== $statuses) {
            $statusColumn = collect($schema->getColumns('leads'))->firstWhere('name', 'status');
            $grammar = $connection->getQueryGrammar();
            $table = $grammar->wrapTable('leads');
            $collation = $statusColumn['collation'] ? ' COLLATE '.$grammar->wrap($statusColumn['collation']) : '';
            $quote = $connection->getPdo()->quote(...);
            $setStatuses = function (array $values) use ($connection, $table, $collation, $quote): void {
                $members = implode(', ', array_map($quote, $values));
                $connection->statement("ALTER TABLE $table MODIFY COLUMN status ENUM($members)$collation NULL DEFAULT 'active'");
            };

            if (! in_array('not_connected', array_map(strtolower(...), $statuses), true)) {
                $setStatuses([...$statuses, 'not_connected']);
                $current[] = 'not_connected';
            }
        }

        $connection->table('leads')->whereRaw('LOWER(RTRIM(status)) = ?', ['not_available'])
            ->update(['status' => 'not_connected']);

        if (isset($setStatuses)) {
            $setStatuses($current);
        }
    }

    public function down(): void
    {
        // The two historical statuses cannot be distinguished after the merge.
    }
};
