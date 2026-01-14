<?php

namespace App\Models;

use CodeIgniter\Model;

class SmpModel extends Model
{
    protected $table = 'smp';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'inn',
        'address'
    ];
    protected $useTimestamps = true;

    /**
     * Быстрый поиск СМП для select
     */
    public function searchSmp(string $term = '', int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table($this->table);
        if ($term !== '') {
            $builder->groupStart()
                ->like('name', $term)
                ->orLike('inn', $term)
                ->groupEnd();
        }
        $builder->orderBy('name', 'ASC')->limit($limit, $offset);
        return $builder->get()->getResultArray();
    }
}