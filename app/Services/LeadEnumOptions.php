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
        $definition = Schema::getColumnType('leads', $field, true);

        if (str_starts_with(strtolower($definition), 'enum(')) {
            return array_map(stripcslashes(...), str_getcsv(substr($definition, 5, -1), ',', "'", '\\'));
        }

        return config("lead-enums.{$field}.values");
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
