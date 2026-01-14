<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseController extends Controller
{
    use ResponseTrait;

    protected $helpers = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        // Настройки для API
        $this->response->setHeader('Content-Type', 'application/json');
    }
    /**
     * Валидация ID
     */
    protected function validateId(int $id): bool
    {
        return $id > 0;
    }

    /**
     * Валидация и обработка входящих данных
     */
    protected function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0') {
                $errors[$field] = 'Это поле обязательно для заполнения';
            }
        }
        return $errors;
    }

    /**
     * Параметры пагинации
     */
    protected function getPaginationParams(): array
    {
        return [
            'page' => (int)($this->request->getGet('page') ?? 1),
            'per_page' => (int)($this->request->getGet('per_page') ?? 10)
        ];
    }

    /**
     * Фильтры из запроса
     */
    protected function getFilters(array $allowedFilters = []): array
    {
        $filters = [];
        foreach ($allowedFilters as $filter) {
            $value = $this->request->getGet($filter);
            if ($value !== null && $value !== '') {
                $filters[$filter] = $value;
            }
        }
        return $filters;
    }

    /**
     * Валидация файла
     */
    protected function validateUploadedFile($file, array $allowedExtensions = []): array
    {
        $errors = [];

        if (!$file || !$file->isValid()) {
            $errors[] = 'Файл не загружен или содержит ошибки';
        }

        if (!empty($allowedExtensions) && !in_array($file->getExtension(), $allowedExtensions)) {
            $errors[] = 'Разрешены только файлы с расширениями: ' . implode(', ', $allowedExtensions);
        }

        return $errors;
    }
}