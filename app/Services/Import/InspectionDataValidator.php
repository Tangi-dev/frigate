<?php

namespace App\Services\Import;

class InspectionDataValidator
{
    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        if (empty($row[6])) {
            $errors[] = "Строка {$rowNumber}: Не указан контролирующий орган";
        }

        if (empty($row[7]) || empty($row[8])) {
            $errors[] = "Строка {$rowNumber}: Не указаны даты начала или окончания";
        }

        return $errors;
    }

    public function validateSmpData(array $row): array
    {
        $errors = [];

        if (empty($row[2])) {
            $errors[] = "Не указано наименование СМП";
        }

        if (empty($row[3])) {
            $errors[] = "Не указан ИНН СМП";
        }

        return $errors;
    }
}