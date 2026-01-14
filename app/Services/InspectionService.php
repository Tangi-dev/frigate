<?php

namespace App\Services;

use App\Interfaces\PlannedInspectionRepositoryInterface;
use App\Services\Import\InspectionDataTransformer;

class InspectionService
{
    private InspectionDataTransformer $transformer;
    protected PlannedInspectionRepositoryInterface $inspectionRepository;

    public function __construct(
        PlannedInspectionRepositoryInterface $inspectionRepository,
        InspectionDataTransformer $transformer
    )
    {
        $this->inspectionRepository = $inspectionRepository;
        $this->transformer = $transformer;
    }

    /**
     * Возвращает инспекции в формате, готовом для API
     * @param array $params
     * @return array
     */
    public function listInspections(array $params): array
    {
        $params['page'] = max(1, (int)($params['page'] ?? 1));
        $params['per_page'] = max(1, (int)($params['per_page'] ?? 10));
        $params['filters'] = $params['filters'] ?? [];

        return $this->inspectionRepository->getInspections($params);
    }

    public function getAuthorities(): array
    {
        return $this->inspectionRepository->getAuthoritiesList();
    }

    public function findInspection(int $id): ?array
    {
        return $this->inspectionRepository->findWithSmp($id);
    }

    /**
     * Создать проверку
     * @param array $data
     * @return array|null
     */
    public function createInspection(array $data): ?array
    {
        return $this->inspectionRepository->createInspection($data);
    }

    /**
     * Сгенерировать следующий номер проверки
     */
    public function generateInspectionNumber(): string
    {
        return $this->inspectionRepository->generateInspectionNumber();
    }

    /**
     * Обновить проверку
     * @param int $id
     * @param array $data
     * @return array|false
     */
    public function updateInspection(int $id, array $data)
    {
        return $this->inspectionRepository->updateInspection($id, $data);
    }

    /**
     * Удалить проверку
     * @param int $id
     * @return bool
     */
    public function deleteInspection(int $id): bool
    {
        return $this->inspectionRepository->deleteInspection($id);
    }

    public function processInspection(array $row, int $smpId, bool $updateExisting): array
    {
        $result = [
            'action' => 'skipped',
            'errors' => []
        ];

        try {
            $inspectionData = $this->transformer->transformRow($row, $smpId);
            $existingInspection = $this->findExistingInspection($row, $inspectionData['inspection_number']);

            if ($existingInspection && $updateExisting) {
                $this->updateInspection($existingInspection['id'], $inspectionData);
                $result['action'] = 'updated';
            } else {
                $this->createInspection($inspectionData);
                $result['action'] = 'created';
            }

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    private function findExistingInspection(array $row, string $inspectionNumber): ?array
    {
        if (!empty($row[0])) {
            return $this->inspectionRepository->findById((int)$row[0]);
        }

        return $this->inspectionRepository->findByInspectionNumber($inspectionNumber);
    }
}
