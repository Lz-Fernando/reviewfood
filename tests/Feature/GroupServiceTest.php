<?php

namespace Tests\Feature;

use App\Enums\GroupInvitationStatus;
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

    public function test_it_invites_a_partner_with_normalized_email_and_hashed_token(): void
    {
        $inviter = User::factory()->create(['email' => 'joao@example.com']);
        $group = $this->service->createPersonalGroup($inviter);

        $result = $this->service->invite($group, $inviter, '  Maria@Example.COM  ');

        $invitation = $result['invitation'];
        $plainToken = $result['token'];

        $this->assertSame(64, strlen($plainToken));
        $this->assertNotSame($plainToken, $invitation->token_hash);
        $this->assertSame(hash('sha256', $plainToken), $invitation->token_hash);
        $this->assertSame(GroupInvitationStatus::PENDING, $invitation->status);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation->id,
            'group_id' => $group->id,
            'invited_by_user_id' => $inviter->id,
            'invited_email' => 'maria@example.com',
            'token_hash' => hash('sha256', $plainToken),
            'status' => GroupInvitationStatus::PENDING->value,
        ]);
    }

    public function test_it_rejects_self_invitation_case_insensitively(): void
    {
        $inviter = User::factory()->create(['email' => 'joao@example.com']);
        $group = $this->service->createPersonalGroup($inviter);

        $this->expectException(\DomainException::class);

        $this->service->invite($group, $inviter, '  JOAO@EXAMPLE.COM ');
    }

    public function test_it_rejects_invitation_from_non_member(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $group = $this->service->createPersonalGroup($owner);
        $this->service->createPersonalGroup($outsider);

        $this->expectException(\DomainException::class);

        $this->service->invite($group, $outsider, 'parceira@example.com');
    }

    public function test_it_rejects_invitation_when_group_already_has_two_active_members(): void
    {
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $group = $this->service->createPersonalGroup($memberA);

        $group->memberships()->create([
            'user_id' => $memberB->id,
            'status' => GroupMemberStatus::ACTIVE,
            'joined_at' => now(),
        ]);

        $this->expectException(\DomainException::class);

        $this->service->invite($group, $memberA, 'terceiro@example.com');
    }

    public function test_it_rejects_duplicate_valid_pending_invitation(): void
    {
        $inviter = User::factory()->create();
        $group = $this->service->createPersonalGroup($inviter);

        $this->service->invite($group, $inviter, 'maria@example.com');

        $this->expectException(\DomainException::class);

        $this->service->invite($group, $inviter, '  MARIA@example.com ');
    }

    public function test_it_expires_old_invitation_and_allows_reinvite_after_expiration(): void
    {
        $inviter = User::factory()->create();
        $group = $this->service->createPersonalGroup($inviter);

        $first = $this->service->invite($group, $inviter, 'maria@example.com');

        $this->travel(8)->days();

        $second = $this->service->invite($group, $inviter, 'maria@example.com');

        $this->assertNotSame($first['invitation']->id, $second['invitation']->id);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $first['invitation']->id,
            'status' => GroupInvitationStatus::EXPIRED->value,
        ]);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $second['invitation']->id,
            'status' => GroupInvitationStatus::PENDING->value,
        ]);
    }
}
