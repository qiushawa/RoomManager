<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Classroom;

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
}
