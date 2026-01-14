<?php

namespace App\Interfaces;

interface FileProcessorInterface
{
    public function process(string $filepath, array $options = []): array;
}