<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlannedInspections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'smp_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID СМП'
            ],
            'inspection_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'controlling_authority' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false
            ],
            'planned_duration' => [
                'type' => 'INT',
                'constraint' => 4,
                'null' => false
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'planned'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('smp_id');
        $this->forge->addKey('inspection_number');
        $this->forge->createTable('planned_inspections');
    }

    public function down()
    {
        $this->forge->dropTable('planned_inspections');
    }
}

