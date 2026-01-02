<?php


namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Users extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $data = [
            ['id' => 1, 'name' => 'Иван', 'email' => 'ivan@test.ru'],
            ['id' => 2, 'name' => 'Мария', 'email' => 'maria@test.ru'],
        ];

        return $this->respond($data);
    }
}