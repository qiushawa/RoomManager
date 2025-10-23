<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Classroom;
use App\Models\BlacklistReason;
use App\Models\Manager;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_classroom(): void
    {
        $data = [
            'active' => 1,
            'room_id' => 'ABCDEFG',
            'room_name' => '測試教室',
        ];
        Classroom::create($data);
        // 驗證資料庫是否包含該資料
        $this->assertDatabaseHas('classroom', $data);

    }

    public function test_it_can_create_a_blacklist_reason(): void
    {
        $data = [
            'reason_id' => 99,
            'reason' => '測試原因',
        ];
        BlacklistReason::create($data);
        // 驗證資料庫是否包含該資料
        $this->assertDatabaseHas('blacklist_reason', $data);
    }

    public function test_it_can_create_a_manager(): void
    {
        $data = [
            'account' => 'testmanager',
            'password' => bcrypt('password123'),
            'name' => '測試管理者',
            'email' => 'testmanager@example.com',
        ];
        Manager::create($data);
        // 驗證資料庫是否包含該資料
        $this->assertDatabaseHas('manager', $data);

    }
}
