<?php

namespace App\Services\Import;

use App\Repositories\DbPlannedInspectionRepository;
use App\Repositories\SmpRepository;
use App\Services\InspectionService;
use App\Services\SmpService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService
{
    /**
     * Экспорт проверок в Excel
     */
    public function exportInspections(array $inspections): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки столбцов
        $headers = [
            'ID проверки',
            'ID СМП',
            'Наименование СМП',
            'ИНН',
            'Адрес',
            'Номер проверки',
            'Контролирующий орган',
            'Дата начала',
            'Дата окончания',
            'Плановая длительность (дни)',
            'Статус',
            'Примечания',
            'Дата создания',
            'Дата обновления'
        ];

        // Устанавливаем заголовки
        $sheet->fromArray($headers, null, 'A1');

        // Стили для заголовков
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Заполняем данными
        $row = 2;
        foreach ($inspections as $inspection) {
            $sheet->setCellValue('A' . $row, $inspection['id'] ?? '');
            $sheet->setCellValue('B' . $row, $inspection['smp_id'] ?? '');
            $sheet->setCellValue('C' . $row, $inspection['smp_name'] ?? '');
            $sheet->setCellValue('D' . $row, $inspection['smp_inn'] ?? '');
            $sheet->setCellValue('E' . $row, $inspection['smp_address'] ?? '');
            $sheet->setCellValue('F' . $row, $inspection['inspection_number'] ?? '');
            $sheet->setCellValue('G' . $row, $inspection['controlling_authority'] ?? '');
            $sheet->setCellValue('H' . $row, $inspection['start_date'] ?? '');
            $sheet->setCellValue('I' . $row, $inspection['end_date'] ?? '');
            $sheet->setCellValue('J' . $row, $inspection['planned_duration'] ?? '');
            $sheet->setCellValue('K' . $row, $inspection['status'] ?? '');
            $sheet->setCellValue('L' . $row, $inspection['notes'] ?? '');
            $sheet->setCellValue('M' . $row, $inspection['created_at'] ?? '');
            $sheet->setCellValue('N' . $row, $inspection['updated_at'] ?? '');

            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Стили для данных
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:N' . $lastRow)->applyFromArray($dataStyle);
        }

        // Фильтры
        if ($row > 2) {
            $sheet->setAutoFilter('A1:N' . ($row - 1));
        }

        // Создаем директорию если не существует
        $uploadDir = WRITEPATH . 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерируем имя файла
        $filename = 'planned_inspections_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = $uploadDir . $filename;

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Импорт проверок из Excel
     */
    public function importInspections(string $filepath, bool $updateExisting = true): array
    {
        $plannedInspectionRepo = new DbPlannedInspectionRepository();
        $smpRepo = new SmpRepository();
        $validator = new InspectionDataValidator();
        $transformer = new InspectionDataTransformer();
        $smpService = new SmpService($smpRepo);
        $inspectionService = new InspectionService($plannedInspectionRepo, $transformer);

        $processor = new ExcelImportProcessor($validator, $smpService, $inspectionService);
        $options = ['update_existing' => $updateExisting];

        return $processor->process($filepath, $options);
    }

    /**
     * Нормализация даты из разных форматов
     */
    private function normalizeDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Если это объект DateTime (из Excel)
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }

        // Пытаемся распознать разные форматы
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $date;
    }

    /**
     * Генерация шаблона Excel для импорта
     */
    public function generateImportTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки с пояснениями
        $headers = [
            'ID проверки (оставьте пустым для новой)',
            'ID СМП (оставьте пустым, если будете указывать ИНН)',
            'Наименование СМП*',
            'ИНН*',
            'Адрес',
            'Номер проверки*',
            'Контролирующий орган*',
            'Дата начала (ГГГГ-ММ-ДД)*',
            'Дата окончания (ГГГГ-ММ-ДД)*',
            'Плановая длительность (дни)',
            'Статус (planned, in_progress, completed, cancelled)',
            'Примечания',
            'Дата создания (заполнится автоматически)',
            'Дата обновления (заполнится автоматически)'
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Пример данных
        $examples = [
            '',
            '',
            'ООО "Ромашка"',
            '1234567890',
            'г. Москва, ул. Ленина, д. 1',
            'ПР-2024-001',
            'Роспотребнадзор',
            '2024-01-15',
            '2024-01-20',
            '5',
            'planned',
            'Плановая проверка',
            '',
            ''
        ];

        $sheet->fromArray($examples, null, 'A2');

        // Стили для заголовков
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Стиль для примера
        $exampleStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
            'font' => ['italic' => true, 'color' => ['rgb' => '7F7F7F']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet->getStyle('A2:N2')->applyFromArray($exampleStyle);

        // Автоширина
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Высота строки для заголовка
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Создаем директорию если не существует
        $uploadDir = WRITEPATH . 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Сохраняем файл
        $filename = 'import_template_' . date('Y-m-d') . '.xlsx';
        $filepath = $uploadDir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Генерация номера проверки
     */
    private function generateInspectionNumber(): string
    {
        return 'ПР-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}