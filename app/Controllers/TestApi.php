<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class TestApi extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->respond([
            'message' => 'API работает!',
            'endpoint' => '/api/inspections',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function inspections()
    {
        $perPage = $this->request->getGet('per_page') ?? 10;
        $page = $this->request->getGet('page') ?? 1;

        // Тестовые данные
        $inspections = [
            [
                'id' => 1,
                'smp_id' => 1,
                'smp_name' => 'ООО "Колосок"',
                'controlling_authority' => 'Роспотребнадзор1',
                'inspection_number' => 'ПП-2024-01-0001',
                'start_date' => '2024-01-15',
                'end_date' => '2024-01-20',
                'planned_duration' => 6,
                'status' => 'planned',
                'created_at' => '2024-01-01T10:00:00',
                'updated_at' => '2024-01-01T10:00:00'
            ],
            [
                'id' => 2,
                'smp_id' => 2,
                'smp_name' => 'ООО "Васильев и Ко"',
                'controlling_authority' => 'Налоговая',
                'inspection_number' => 'ПП-2024-01-0002',
                'start_date' => '2024-02-01',
                'end_date' => '2024-02-10',
                'planned_duration' => 10,
                'status' => 'in_progress',
                'created_at' => '2024-01-02T11:00:00',
                'updated_at' => '2024-01-02T11:00:00'
            ]
        ];

        // Пагинация
        $totalItems = count($inspections);
        $totalPages = ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($inspections, $offset, $perPage);

        $response = [
            'success' => true,
            'data' => $paginatedData,
            'pager' => [
                'currentPage' => (int)$page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'perPage' => (int)$perPage
            ]
        ];

        return $this->respond($response);
    }

    public function authorities()
    {
        $authorities = [
            'Роспотребнадзор1',
            'Налоговая',
            'Природнадзор',
            'Пожарная служба',
            'Трудовая инспекция',
            'Росздравнадзор'
        ];

        return $this->respond([
            'success' => true,
            'data' => $authorities
        ]);
    }
}