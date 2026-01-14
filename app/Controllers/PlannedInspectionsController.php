<?php

namespace App\Controllers;

use App\Models\SmpModel;
use App\Services\Import\ExcelService;
use App\Services\InspectionService;
use CodeIgniter\API\ResponseTrait;

class PlannedInspectionsController extends BaseController
{
    use ResponseTrait;

    protected InspectionService $inspectionService;
    protected ExcelService $excelService;

    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        try {
            /** @var InspectionService $svc */
            $svc = service('inspectionService');
            $this->inspectionService = $svc;
            $this->excelService = new ExcelService();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Главная страница с перечнем проверок
     */
    public function index()
    {
        try {
            $page = (int)($this->request->getGet('page') ?? 1);
            $perPage = (int)($this->request->getGet('per_page') ?? 10);
            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'filters' => [
                    'smp_name' => $this->request->getGet('smp_name'),
                    'controlling_authority' => $this->request->getGet('controlling_authority'),
                    'status' => $this->request->getGet('status'),
                    'start_date' => $this->request->getGet('start_date'),
                    'end_date' => $this->request->getGet('end_date')
                ]
            ];

            $result = $this->inspectionService->listInspections($params);

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'error: ' . $e]);
        }
    }

    /**
     * Получение всех контролирующих органов
     */
    public function getAuthorities()
    {
        $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Content-Type', 'application/json');

        try {
            $authorities = $this->inspectionService->getAuthorities();

            $response = [
                'success' => true,
                'data' => $authorities,
                'total' => count($authorities)
            ];

            return $this->response->setJSON($response);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'error: ' . $e]);
        }
    }

    /**
     * Поиск СМП для автокомплита
     */
    public function smpSearch()
    {
        try {
            $q = $this->request->getGet('q') ?? '';
            $limit = (int)($this->request->getGet('limit') ?? 20);

            $smpModel = new SmpModel();
            $rows = $smpModel->searchSmp($q, $limit);

            // Вернуть массив объектов
            return $this->response->setJSON(array_values($rows));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'error: ' . $e]);
        }
    }

    /**
     * Обработка OPTIONS для CORS
     */
    public function options()
    {
        $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Max-Age', '86400')
            ->setStatusCode(200);

        return $this->response;
    }

    /**
     * Создать проверку
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            if (!$data || !is_array($data)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ]);
            }

            // Простая валидация полей
            $required = ['smp_id', 'controlling_authority', 'start_date', 'end_date', 'planned_duration'];
            $errors = [];
            foreach ($required as $f) {
                if (empty($data[$f]) && $data[$f] !== 0 && $data[$f] !== '0') {
                    $errors[$f] = 'This field is required';
                }
            }

            if (!empty($errors)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'errors' => $errors
                ]);
            }

            // Маппинг/очистка данных
            $payload = [
                'smp_id' => (int)$data['smp_id'],
                'inspection_number' => $data['inspection_number'] ?? null,
                'controlling_authority' => trim($data['controlling_authority']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'planned_duration' => (int)$data['planned_duration'],
                'status' => $data['status'] ?? 'planned',
                'notes' => $data['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Сгенерирую автоматически номер проверки, если не передали
            if (empty($payload['inspection_number'])) {
                $payload['inspection_number'] = $this->inspectionService->generateInspectionNumber();
            }
            $created = $this->inspectionService->createInspection($payload);

            return $this->response->setJSON(['success' => true, 'data' => $created]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Ошибка при импорте: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $id = (int)$id;
        if (!$this->validateId($id)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'ID not found'
            ]);
        }

        if (!$this->inspectionService->findInspection($id)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Inspection not found'
            ]);
        }

        $result = $this->inspectionService->deleteInspection($id);
        if ($result) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => true,
                'message' => 'Проверка успешно удалена'
            ]);
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Не удалось удалить проверку'
            ]);
        }
    }

    /**
     * Получить проверку по ID
     */
    public function show($id)
    {
        try {
            $id = (int)$id;

            if (!$this->validateId($id)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Invalid inspection ID'
                    ]);
            }

            $inspection = $this->inspectionService->findInspection($id);

            if (!$inspection) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Проверка не найдена'
                    ]);
            }

            return $this->response
                ->setJSON([
                    'success' => true,
                    'message' => 'Проверка найдена',
                    'data' => $inspection
                ]);

        } catch (\Throwable $e) {
            log_message('error', 'Show inspection error: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Ошибка при получении проверки'
                ]);
        }
    }

    /**
     * Обновить проверку
     */
    public function update($id)
    {
        try {
            $id = (int)$id;

            // Валидация ID
            if (!$this->validateId($id)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Invalid inspection ID'
                    ]);
            }

            // Проверка существования
            $existingInspection = $this->inspectionService->findInspection($id);
            if (!$existingInspection) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Проверка не найдена'
                    ]);
            }

            // Получение данных
            $data = $this->request->getJSON(true);
            if (!$data || !is_array($data)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Invalid JSON payload'
                    ]);
            }

            // Валидация
            $required = ['smp_id', 'controlling_authority', 'start_date', 'end_date', 'planned_duration'];
            $errors = [];
            foreach ($required as $field) {
                if (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0') {
                    $errors[$field] = 'This field is required';
                }
            }

            if (!empty($errors)) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Ошибка валидации',
                        'errors' => $errors
                    ]);
            }

            $updateData = [
                'smp_id' => (int)$data['smp_id'],
                'controlling_authority' => trim($data['controlling_authority']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'planned_duration' => (int)$data['planned_duration'],
                'status' => $data['status'] ?? $existingInspection['status'] ?? 'planned',
                'notes' => $data['notes'] ?? $existingInspection['notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($data['inspection_number'])) {
                $updateData['inspection_number'] = trim($data['inspection_number']);
            }

            // Обновление
            $result = $this->inspectionService->updateInspection($id, $updateData);

            if (is_array($result) && isset($result['errors'])) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Ошибка при обновлении',
                        'errors' => $result['errors']
                    ]);
            }

            if ($result === false) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Не удалось обновить проверку'
                    ]);
            }

            // Получаем обновленную запись
            $updatedInspection = $this->inspectionService->findInspection($id);

            return $this->response
                ->setJSON([
                    'success' => true,
                    'message' => 'Проверка успешно обновлена',
                    'data' => $updatedInspection
                ]);

        } catch (\Throwable $e) {
            log_message('error', 'Update inspection error: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Ошибка при обновлении проверки'
                ]);
        }
    }

    public function export()
    {
        try {
            // Данные с учетом фильтров
            $params = [
                'page' => 1,
                'per_page' => 10000,
                'filters' => [
                    'smp_name' => $this->request->getGet('smp_name'),
                    'controlling_authority' => $this->request->getGet('controlling_authority'),
                    'status' => $this->request->getGet('status'),
                    'start_date' => $this->request->getGet('start_date'),
                    'end_date' => $this->request->getGet('end_date')
                ]
            ];

            $result = $this->inspectionService->listInspections($params);
            $inspections = $result['data'] ?? [];

            if (empty($inspections)) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Нет данных для экспорта'
                ]);
            }

            $excelService = new ExcelService();
            $filepath = $excelService->exportInspections($inspections);

            return $this->response->download($filepath, null, true)
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filepath) . '"');

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Импорт проверок из Excel
     */
    public function import()
    {
        try {
            $file = $this->request->getFile('excel_file');
            $errors = $this->validateUploadedFile($file, ['xlsx', 'xls', 'csv']);
            if ($errors) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Ошибки загрузки файла: ' . implode('; ', $errors)
                ]);
            }

            // Перемещаем файл
            $uploadPath = WRITEPATH . 'uploads/imports/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $filename = $file->getRandomName();
            $filepath = $uploadPath . $filename;
            $file->move($uploadPath, $filename);

            // Получаем параметр обновления существующих записей
            $updateExisting = (bool)$this->request->getPost('update_existing') ?? true;
            $excelService = new ExcelService();
            $result = $excelService->importInspections($filepath, $updateExisting);

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ошибка при импорте: ' . $e->getMessage()
            ]);
        }
    }

    public function downloadTemplate()
    {
        try {
            $excelService = new ExcelService();
            if (!$excelService) {
                throw new \Exception('Не удалось создать ExcelService');
            }

            $filepath = $excelService->generateImportTemplate();

            return $this->response->download($filepath, null, true)
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="template_import_inspections.xlsx"');

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ошибка при генерации шаблона: ' . $e->getMessage()
            ]);
        }
    }
}