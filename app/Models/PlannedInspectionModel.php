<?php

namespace App\Models;

use CodeIgniter\Model;

class PlannedInspectionModel extends Model
{
    protected $table = 'planned_inspections';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'smp_id',
        'inspection_number',
        'controlling_authority',
        'start_date',
        'end_date',
        'planned_duration',
        'status',
        'notes'
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'smp_id' => 'required|integer',
        'controlling_authority' => 'required|min_length[3]|max_length[255]',
        'start_date' => 'required|valid_date',
        'end_date' => 'required|valid_date',
        'planned_duration' => 'required|integer|greater_than[0]',
        'status' => 'required|in_list[planned,in_progress,completed,cancelled]'
    ];

    protected $validationMessages = [
        'smp_id' => [
            'required' => 'Выберите СМП',
            'integer' => 'Неверный формат СМП'
        ],
        'controlling_authority' => [
            'required' => 'Укажите контролирующий орган',
            'min_length' => 'Название органа должно содержать минимум 3 символа'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Поиск/фильтрация проверок
     * $filters: ['q','smp_name','controlling_authority','status','start_date','end_date']
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $db = $this->db;
        if (!method_exists($db, 'tableExists') || !$db->tableExists($this->table)) {
            return ['data' => [], 'total' => 0];
        }

        // сначала 'smp', затем 'smp_entities'
        $smpTable = null;
        if ($db->tableExists('smp')) {
            $smpTable = 'smp';
        } elseif ($db->tableExists('smp_entities')) {
            $smpTable = 'smp_entities';
        }

        $builder = $this->db->table($this->table . ' pi');
        // Если таблица СМП найдена, присоединяемся к ней, иначе возвращаем колонки inspections без СМП
        if ($smpTable) {
            // Вытащил id, name и inn из таблицы СМП, чтобы в ответе всегда были поля smp_id, smp_name, smp_inn
            $builder->select('pi.*, s.id AS smp_id, s.name AS smp_name, s.inn AS smp_inn');
            $builder->join($smpTable . ' s', 's.id = pi.smp_id', 'left');
        } else {
            $builder->select('pi.*');
        }

        if (!empty($filters['q'])) {
            $builder->groupStart()
                ->like('pi.inspection_number', $filters['q'])
                ->orLike('s.name', $filters['q'])
                ->orLike('s.inn', $filters['q'])
                ->orLike('pi.controlling_authority', $filters['q'])
                ->groupEnd();
        }

        if (!empty($filters['smp_name'])) {
            if ($smpTable) {
                $builder->like('s.name', $filters['smp_name']);
            }
        }

        if (!empty($filters['controlling_authority'])) {
            $builder->like('pi.controlling_authority', $filters['controlling_authority']);
        }

        if (!empty($filters['status'])) {
            $builder->where('pi.status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $builder->where('pi.start_date >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('pi.end_date <=', $filters['end_date']);
        }

        $total = (clone $builder)->countAllResults(false);

        $builder->orderBy('pi.start_date', 'DESC');
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        $rows = $builder->get()->getResultArray();

        return ['data' => $rows, 'total' => (int)$total];
    }

    /**
     * Получить запись с данными СМП
     */
    public function findWithSmp(int $id)
    {
        $db = $this->db;
        $smpTable = null;
        if (method_exists($db, 'tableExists') && $db->tableExists('smp')) {
            $smpTable = 'smp';
        } elseif (method_exists($db, 'tableExists') && $db->tableExists('smp_entities')) {
            $smpTable = 'smp_entities';
        }

        $builder = $this->db->table($this->table . ' pi');

        if ($smpTable) {
            $builder->select('pi.*, s.id AS smp_id, s.name AS smp_name, s.inn AS smp_inn');
            $builder->join($smpTable . ' s', 's.id = pi.smp_id', 'left');
        } else {
            $builder->select('pi.*');
        }

        return $builder->where('pi.id', $id)->get()->getRowArray();
    }

    /**
     * Генерация номера проверки
     */
    public function generateInspectionNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "ПП-{$year}-{$month}-";

        $lastNumber = $this->select('inspection_number')
            ->like('inspection_number', $prefix)
            ->orderBy('inspection_number', 'DESC')
            ->first();

        if ($lastNumber) {
            $parts = explode('-', $lastNumber['inspection_number']);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Возвращает набор данных
     */
    public function getInspectionsWithSmp(array $params = []): array
    {
        $db = $this->db;
        if (!method_exists($db, 'tableExists') || !$db->tableExists($this->table)) {
            return [
                'success' => true,
                'data' => [],
                'pager' => [
                    'currentPage' => 1,
                    'totalPages' => 0,
                    'totalItems' => 0,
                    'perPage' => $params['per_page'] ?? 10
                ],
                'meta' => [
                    'total' => 0,
                    'per_page' => (int)($params['per_page'] ?? 10),
                    'current_page' => 1,
                    'last_page' => 0,
                    'from' => 0,
                    'to' => 0
                ]
            ];
        }

        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, (int)($params['per_page'] ?? 10));
        $offset = ($page - 1) * $perPage;

        $result = $this->search($params['filters'] ?? [], $perPage, $offset);
        $rows = $result['data'] ?? [];
        $total = isset($result['total']) ? (int)$result['total'] : count($rows);

        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

        return [
            'success' => true,
            'data' => $rows,
            'pager' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $total,
                'perPage' => $perPage
            ],
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => ($total > 0) ? ($offset + 1) : 0,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }

    /**
     * Возвращаем список уникальных контролирующих органов из таблицы проверок
     */
    public function getAuthoritiesList(): array
    {
        $db = $this->db;
        if (!method_exists($db, 'tableExists') || !$db->tableExists($this->table)) {
            return [];
        }

        $builder = $db->table($this->table);
        $builder->distinct()->select('controlling_authority AS authority');
        $builder->where('controlling_authority IS NOT NULL', null, false)
                ->where("TRIM(controlling_authority) != ''", null, false)
                ->orderBy('controlling_authority', 'ASC');

        $rows = $builder->get()->getResultArray();
        $list = array_map(function ($r) {
            return $r['authority'];
        }, $rows);

        return $list;
    }
}
