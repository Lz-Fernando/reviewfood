<?php

namespace Tests\Feature;

use App\Enums\GroupMemberStatus;
use App\Enums\GroupStatus;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupServiceTest extends TestCase
{
    use RefreshDatabase;

    private GroupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GroupService::class);
    }

    /**
     * A basic feature test example.
     */
    public function test_it_creates_a_personal_active_group_for_a_user(): void
    {
        $user = User::factory()->create();

        $group = $this->service->createPersonalGroup($user);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'status' => GroupStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => GroupMemberStatus::ACTIVE->value,
            'left_at' => null,
        ]);
    }

    public function test_it_error_creates_a_duplicate_personal_active_group_for_a_user(): void
    {
        $user = User::factory()->create();

        $this->service->createPersonalGroup($user);

        $this->expectException(\DomainException::class);

        $this->service->createPersonalGroup($user);
    }
}
