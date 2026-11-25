<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class LeadEnumOptions
{
    public function values(string $field): array
    {
        $values = self::parseDefinition(Schema::getColumnType('leads', $field, true))
            ?? config("lead-enums.{$field}.values");

        return $field === 'status' ? self::currentStatuses($values) : $values;
    }

    public static function currentStatuses(array $values): array
    {
        return array_values(array_filter($values, fn ($value) => strcasecmp($value, 'not_available') !== 0));
    }

    public static function parseDefinition(string $definition): ?array
    {
        if (str_starts_with(strtolower($definition), 'enum(')) {
            return array_map(stripcslashes(...), str_getcsv(substr($definition, 5, -1), ',', "'", '\\'));
        }

        return null;
    }

    public function rule(string $field): In
    {
        return Rule::in($this->values($field));
    }

    public function options(string $field): array
    {
        $formatLabel = in_array($field, ['status', 'type', 'visit_status'], true);

        return array_map(fn ($value) => [
            'label' => $formatLabel ? Str::headline($value) : $value,
            'value' => $value,
        ], $this->values($field));
    }
}
