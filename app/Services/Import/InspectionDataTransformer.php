<?php

namespace App\Services\Import;

class InspectionDataTransformer
{
    private DateNormalizer $dateNormalizer;
    private InspectionNumberGenerator $numberGenerator;

    public function __construct()
    {
        $this->dateNormalizer = new DateNormalizer();
        $this->numberGenerator = new InspectionNumberGenerator();
    }

    public function transformRow(array $row, int $smpId): array
    {
        return [
            'smp_id' => $smpId,
            'inspection_number' => $this->getInspectionNumber($row),
            'controlling_authority' => $this->getControllingAuthority($row),
            'start_date' => $this->getStartDate($row),
            'end_date' => $this->getEndDate($row),
            'planned_duration' => $this->getPlannedDuration($row),
            'status' => $this->getStatus($row),
            'notes' => $this->getNotes($row)
        ];
    }

    private function getInspectionNumber(array $row): string
    {
        return !empty($row[5]) ? trim($row[5]) : $this->numberGenerator->generate();
    }

    private function getControllingAuthority(array $row): string
    {
        return trim($row[6]);
    }

    private function getStartDate(array $row): string
    {
        return $this->dateNormalizer->normalize($row[7]);
    }

    private function getEndDate(array $row): string
    {
        return $this->dateNormalizer->normalize($row[8]);
    }

    private function getPlannedDuration(array $row): int
    {
        return !empty($row[9]) ? (int)$row[9] : 0;
    }

    private function getStatus(array $row): string
    {
        return !empty($row[10]) ? trim($row[10]) : 'planned';
    }

    private function getNotes(array $row): ?string
    {
        return !empty($row[11]) ? trim($row[11]) : null;
    }
}