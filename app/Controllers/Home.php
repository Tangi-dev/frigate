<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return view('welcome_message');
    }

    public function test()
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'API живи и работай!',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
    }

    // Метод для OPTIONS запросов
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Authorization')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH')
            ->setHeader('Access-Control-Max-Age', '86400')
            ->setStatusCode(200);
    }
}