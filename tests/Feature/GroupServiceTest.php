<?php

namespace Tests\Feature;

use App\Enums\GroupInvitationStatus;
use App\Enums\GroupMemberStatus;
use App\Enums\GroupStatus;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Database\QueryException;
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

    public function test_it_accepts_invitation_and_closes_empty_personal_group(): void
    {
        $userA = User::factory()->create(['email' => 'joao@example.com']);
        $userB = User::factory()->create(['email' => 'maria@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $personalGroupB = $this->service->createPersonalGroup($userB);

        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $joinedGroup = $this->service->acceptInvitation($inviteResult['invitation'], $userB);

        $this->assertSame($groupA->id, $joinedGroup->id);

        $this->assertDatabaseHas('groups', [
            'id' => $personalGroupB->id,
            'status' => GroupStatus::INACTIVE->value,
        ]);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $personalGroupB->id,
            'user_id' => $userB->id,
            'status' => GroupMemberStatus::LEFT->value,
        ]);

        $oldMembership = $personalGroupB->memberships()->where('user_id', $userB->id)->firstOrFail();
        $this->assertNotNull($oldMembership->left_at);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupA->id,
            'user_id' => $userB->id,
            'status' => GroupMemberStatus::ACTIVE->value,
            'left_at' => null,
        ]);

        $this->assertSame(
            2,
            $groupA->memberships()->where('status', GroupMemberStatus::ACTIVE)->count()
        );

        $inviteResult['invitation']->refresh();
        $this->assertSame(GroupInvitationStatus::ACCEPTED, $inviteResult['invitation']->status);
        $this->assertNotNull($inviteResult['invitation']->responded_at);
    }

    public function test_it_accepts_invitation_when_user_has_no_existing_group(): void
    {
        $userA = User::factory()->create(['email' => 'joao@example.com']);
        $userB = User::factory()->create(['email' => 'maria@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $joinedGroup = $this->service->acceptInvitation($inviteResult['invitation'], $userB);

        $this->assertSame($groupA->id, $joinedGroup->id);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupA->id,
            'user_id' => $userB->id,
            'status' => GroupMemberStatus::ACTIVE->value,
        ]);
    }

    public function test_it_rejects_accepting_expired_invitation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['email' => 'maria@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $this->travel(8)->days();

        $this->expectException(\DomainException::class);

        $this->service->acceptInvitation($inviteResult['invitation'], $userB);
    }

    public function test_it_rejects_accepting_invitation_for_different_email(): void
    {
        $userA = User::factory()->create();
        $wrongUser = User::factory()->create(['email' => 'outro@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $this->expectException(\DomainException::class);

        $this->service->acceptInvitation($inviteResult['invitation'], $wrongUser);
    }

    public function test_it_rejects_accepting_already_accepted_invitation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['email' => 'maria@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $this->service->acceptInvitation($inviteResult['invitation'], $userB);

        $this->expectException(\DomainException::class);

        $this->service->acceptInvitation($inviteResult['invitation'], $userB);
    }

    public function test_it_rejects_third_member_accepting_invitation_into_full_group(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['email' => 'maria@example.com']);
        $userC = User::factory()->create(['email' => 'carlos@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $this->service->createPersonalGroup($userB);
        $personalGroupC = $this->service->createPersonalGroup($userC);

        $inviteB = $this->service->invite($groupA, $userA, 'maria@example.com');
        $inviteC = $this->service->invite($groupA, $userA, 'carlos@example.com');

        $this->service->acceptInvitation($inviteB['invitation'], $userB);

        try {
            $this->service->acceptInvitation($inviteC['invitation'], $userC);
            $this->fail('Expected DomainException when a third member tries to join a full group.');
        } catch (\DomainException) {
            // Expected: user C must remain in their own active group
        }

        $this->assertDatabaseHas('groups', [
            'id' => $personalGroupC->id,
            'status' => GroupStatus::ACTIVE->value,
        ]);
        $this->assertSame(2, $groupA->memberships()->where('status', GroupMemberStatus::ACTIVE)->count());
    }

    public function test_database_trigger_blocks_third_active_member_in_group(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        $group = $this->service->createPersonalGroup($userA);
        $group->memberships()->create([
            'user_id' => $userB->id,
            'status' => GroupMemberStatus::ACTIVE,
            'joined_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('A group may have at most two active members in the MVP');

        $group->memberships()->create([
            'user_id' => $userC->id,
            'status' => GroupMemberStatus::ACTIVE,
            'joined_at' => now(),
        ]);
    }

    public function test_it_rejects_accepting_invitation_when_personal_group_has_data(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['email' => 'maria@example.com']);

        $groupA = $this->service->createPersonalGroup($userA);
        $personalGroupB = $this->service->createPersonalGroup($userB);

        $personalGroupB->tags()->create([
            'created_by_user_id' => $userB->id,
            'name' => 'Date',
        ]);

        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $this->expectException(\DomainException::class);

        $this->service->acceptInvitation($inviteResult['invitation'], $userB);
    }

    public function test_it_rejects_accepting_invitation_when_user_already_in_couple_group(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['email' => 'maria@example.com']);
        $userC = User::factory()->create();

        $groupA = $this->service->createPersonalGroup($userA);
        $groupB = $this->service->createPersonalGroup($userB);
        $groupB->memberships()->create([
            'user_id' => $userC->id,
            'status' => GroupMemberStatus::ACTIVE,
            'joined_at' => now(),
        ]);

        $inviteResult = $this->service->invite($groupA, $userA, 'maria@example.com');

        $this->expectException(\DomainException::class);

        $this->service->acceptInvitation($inviteResult['invitation'], $userB);
    }
}
