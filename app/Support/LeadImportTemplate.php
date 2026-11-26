<?php

namespace App\Support;

use App\Imports\AdminLeadsImport;
use App\Services\LeadEnumOptions;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadImportTemplate
{
    public function workbook(LeadEnumOptions $enums): Spreadsheet
    {
        $workbook = new Spreadsheet();
        $leads = $workbook->getActiveSheet()->setTitle('Leads');
        $leads->fromArray([AdminLeadsImport::HEADINGS]);
        $leads->freezePane('B2');
        $leads->getStyle('1:1')->getFont()->setBold(true);
        $leads->getDefaultColumnDimension()->setWidth(21);
        $leads->getStyle('B:B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $leads->getStyle('G:G')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $choices = $workbook->createSheet()->setTitle('Choices');
        $choices->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        foreach (AdminLeadsImport::HEADINGS as $index => $field) {
            if (! isset(config('lead-enums')[$field])) {
                continue;
            }

            $column = Coordinate::stringFromColumnIndex($index + 1);
            $values = $field === 'visit_status'
                ? array_column($enums->options($field), 'label')
                : $enums->values($field);
            $choices->fromArray(array_chunk($values, 1), null, $column.'1');
            $rangeName = 'lead_import_'.$field;
            $workbook->addNamedRange(new NamedRange(
                $rangeName, $choices, '$'.$column.'$1:$'.$column.'$'.count($values),
            ));

            $leads->getDataValidation($column.'2')
                ->setSqref($column.'2:'.$column.'1048576')
                ->setType(DataValidation::TYPE_LIST)
                ->setFormula1($rangeName)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setShowErrorMessage(true)
                ->setError('Choose a value from the dropdown.');
        }

        return $workbook;
    }
}
