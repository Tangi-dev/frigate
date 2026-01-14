<?php

namespace App\Services\Import;

class InspectionNumberGenerator
{
    public function generate(): string
    {
        return 'ПР-' . date('Y') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}