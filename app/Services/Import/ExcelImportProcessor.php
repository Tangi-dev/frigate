<?php

namespace App\Services\Import;

use App\Interfaces\FileProcessorInterface;
use App\Services\InspectionService;
use App\Services\SmpService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportProcessor implements FileProcessorInterface
{
    private InspectionDataValidator $validator;
    private SmpService $smpService;
    private InspectionService $inspectionService;

    public function __construct(
        InspectionDataValidator $validator,
        SmpService $smpService,
        InspectionService $inspectionService
    ) {
        $this->validator = $validator;
        $this->smpService = $smpService;
        $this->inspectionService = $inspectionService;
    }

    public function process(string $filepath, array $options = []): array
    {
        $result = [
            'success' => true,
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $data = $this->loadDataFromFile($filepath);
            $result['total'] = count($data);

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;
                $rowResult = $this->processRow($row, $rowNumber, $options);

                $this->updateResult($result, $rowResult);
            }

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = 'Ошибка при чтении файла: ' . $e->getMessage();
        }

        return $result;
    }

    private function loadDataFromFile(string $filepath): array
    {
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Пропускаем заголовки
        array_shift($data);

        return $data;
    }

    private function processRow(array $row, int $rowNumber, array $options): array
    {
        $rowResult = [
            'action' => 'skipped',
            'errors' => []
        ];

        // Валидация данных
        $validationErrors = $this->validator->validateRow($row, $rowNumber);
        if (!empty($validationErrors)) {
            $rowResult['errors'] = $validationErrors;
            return $rowResult;
        }

        // Поиск или создание СМП
        $smpId = $this->smpService->findOrCreateSmp($row);
        if (!$smpId) {
            $smpErrors = $this->validator->validateSmpData($row);
            $rowResult['errors'] = array_merge($rowResult['errors'], $smpErrors);
            return $rowResult;
        }

        // Обработка проверки
        $updateExisting = $options['update_existing'] ?? true;
        $inspectionResult = $this->inspectionService->processInspection($row, $smpId, $updateExisting);

        return array_merge($rowResult, $inspectionResult);
    }

    private function updateResult(array &$result, array $rowResult): void
    {
        if (!empty($rowResult['errors'])) {
            $result['errors'] = array_merge($result['errors'], $rowResult['errors']);
            $result['skipped']++;
            return;
        }

        switch ($rowResult['action']) {
            case 'created':
                $result['imported']++;
                break;
            case 'updated':
                $result['updated']++;
                break;
            case 'skipped':
                $result['skipped']++;
                break;
        }
    }
}