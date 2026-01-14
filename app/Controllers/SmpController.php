<?php

namespace App\Controllers;

use App\Models\SmpModel;
use CodeIgniter\API\ResponseTrait;

class SmpController extends BaseController
{
    use ResponseTrait;

    protected $smpModel;

    public function __construct()
    {
        $this->smpModel = new SmpModel();
    }

    /**
     * Поиск СМП
     */
    public function search()
    {
        $term = $this->request->getGet('term');
        if (empty($term)) {
            $term = $this->request->getGet('q') ?? '';
        }

        $limit = (int)($this->request->getGet('limit') ?? 20);

        if (empty($term)) {
            return $this->respond([
                'success' => true,
                'data' => []
            ]);
        }

        $smpList = $this->smpModel->like('name', $term)
            ->orLike('inn', $term)
            ->orderBy('name', 'ASC')
            ->findAll($limit);

        return $this->respond([
            'success' => true,
            'data' => $smpList
        ]);
    }

    /**
     * Для выпадающего списка
     */
    public function dropdown()
    {
        $smpList = $this->smpModel->select('id, name, inn')
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => $smpList
        ]);
    }

    /**
     * Создание нового СМП
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        if ($id = $this->smpModel->insert($data)) {
            $smp = $this->smpModel->find($id);

            return $this->respond([
                'success' => true,
                'message' => 'СМП успешно добавлен',
                'data' => $smp
            ]);
        } else {
            return $this->respond([
                'success' => false,
                'errors' => $this->smpModel->errors()
            ], 400);
        }
    }

    public function get($id = null)
    {
        try {
            if (!$id) {
                return $this->fail('ID обязателен', 400);
            }

            $smp = $this->smpModel->find($id);

            if (!$smp) {
                return $this->failNotFound('СМП не найден');
            }

            return $this->respond([
                'success' => true,
                'data' => $smp
            ]);

        } catch (\Exception $e) {
            return $this->failServerError('Ошибка сервера: ' . $e);
        }
    }
}