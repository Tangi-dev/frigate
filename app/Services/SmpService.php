<?php

namespace App\Services;

use App\Interfaces\SmpRepositoryInterface;

class SmpService
{
    private SmpRepositoryInterface $smpRepository;

    public function __construct(SmpRepositoryInterface $smpRepository)
    {
        $this->smpRepository = $smpRepository;
    }

    public function findOrCreateSmp(array $row): ?int
    {
        $smpId = $this->findSmpById($row);

        if (!$smpId) {
            $smpId = $this->findSmpByInn($row);
        }

        if (!$smpId) {
            $smpId = $this->createSmp($row);
        }

        return $smpId;
    }

    private function findSmpById(array $row): ?int
    {
        if (!empty($row[1])) {
            $smp = $this->smpRepository->findById((int)$row[1]);
            return $smp ? $smp['id'] : null;
        }

        return null;
    }

    private function findSmpByInn(array $row): ?int
    {
        if (!empty($row[3])) {
            $inn = trim($row[3]);
            $smp = $this->smpRepository->findByInn($inn);
            return $smp ? $smp['id'] : null;
        }

        return null;
    }

    private function createSmp(array $row): ?int
    {
        $smpName = !empty($row[2]) ? trim($row[2]) : '';
        $smpInn = !empty($row[3]) ? trim($row[3]) : '';

        if (empty($smpName) || empty($smpInn)) {
            return null;
        }

        $smpData = [
            'name' => $smpName,
            'inn' => $smpInn,
            'address' => !empty($row[4]) ? trim($row[4]) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->smpRepository->create($smpData);
    }
}