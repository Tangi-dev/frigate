<?php

namespace App\Services\Import;

class DateNormalizer
{
    public function normalize($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $date;
    }
}