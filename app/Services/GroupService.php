<?php

namespace App\Services;

use App\Enums\GroupInvitationStatus;
use App\Enums\GroupMemberStatus;
use App\Enums\GroupStatus;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupService
{
    public function createPersonalGroup(User $user): Group
    {
        if ($user->memberships()->where('status', GroupMemberStatus::ACTIVE)->exists()) {
            throw new \DomainException('O usuário já possui um grupo ativo.');
        }

        return DB::transaction(function () use ($user): Group {
            $group = Group::create([
                'name' => $user->name,
                'status' => GroupStatus::ACTIVE,
            ]);

            $group->memberships()->create([
                'user_id' => $user->id,
                'status' => GroupMemberStatus::ACTIVE,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }

    public function invite(Group $group, User $inviter, string $email): array
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if ($group->status !== GroupStatus::ACTIVE) {
            throw new \DomainException('O grupo não está ativo');
        }

        if (! $group->memberships()->where('user_id', $inviter->id)->where('status', GroupMemberStatus::ACTIVE)->exists()) {
            throw new \DomainException('O usuário não pertence a esse grupo');
        }

        if ($group->memberships()->where('status', GroupMemberStatus::ACTIVE)->count() >= 2) {
            throw new \DomainException('O grupo já possui 2 usuários');
        }

        if ($normalizedEmail === mb_strtolower(trim($inviter->email))) {
            throw new \DomainException('Não é possivel convidar o próprio e-mail');
        }

        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($group, $inviter, $plainToken, $normalizedEmail): GroupInvitation {
            $existingInvitation = $group->invitations()->where('status', GroupInvitationStatus::PENDING)->whereRaw('LOWER(invited_email) = ?', [$normalizedEmail])->first();

            if ($existingInvitation !== null) {
                if ($existingInvitation->expires_at->isFuture()) {
                    throw new \DomainException('Já existe um convite válido');
                } else {
                    $existingInvitation->update([
                        'status' => GroupInvitationStatus::EXPIRED,
                    ]);
                }
            }

            return $group->invitations()->create([
                'invited_by_user_id' => $inviter->id,
                'invited_email' => $normalizedEmail,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(7),
                'status' => GroupInvitationStatus::PENDING,
            ]);
        });

        return [
            'invitation' => $invitation,
            'token' => $plainToken,
        ];
    }

    public function acceptInvitation(GroupInvitation $invitation, User $user): Group
    {
        return DB::transaction(function () use ($invitation, $user): Group {
            $destineGroup = Group::whereKey($invitation->group_id)->lockForUpdate()->firstOrFail();
            $invite = GroupInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if ($invite->status !== GroupInvitationStatus::PENDING) {
                throw new \DomainException('O status do convite não é pendente');
            } elseif ($invite->expires_at->isPast()) {
                throw new \DomainException('O convite está expirado');
            } elseif (mb_strtolower(trim($user->email)) !== mb_strtolower(trim($invite->invited_email))) {
                throw new \DomainException('O email do convite não pertence a esse usuário');
            } elseif ($destineGroup->status !== GroupStatus::ACTIVE) {
                throw new \DomainException('O grupo não está ativo');
            } elseif ($destineGroup->memberships()->where('status', GroupMemberStatus::ACTIVE)->count() >= 2) {
                throw new \DomainException('O grupo já têm 2 membros ativos');
            }

            $currentMembership = $user->memberships()->where('status', GroupMemberStatus::ACTIVE)->with('group')->first();

            if ($currentMembership !== null) {
                $currentUserGroup = $currentMembership->group;

                if ($currentUserGroup->id === $destineGroup->id
                    || $currentUserGroup->memberships()->where('status', GroupMemberStatus::ACTIVE)->count() !== 1
                    || $currentUserGroup->restaurants()->exists()
                    || $currentUserGroup->categories()->exists()
                    || $currentUserGroup->tags()->exists()) {
                    throw new \DomainException('O usuário já está em um grupo');
                }

                $user->memberships()->where('status', GroupMemberStatus::ACTIVE)->update([
                    'status' => GroupMemberStatus::LEFT,
                    'left_at' => now(),
                ]);

                $currentUserGroup->update([
                    'status' => GroupStatus::INACTIVE,
                ]);
            }

            $destineGroup->memberships()->create([
                'user_id' => $user->id,
                'status' => GroupMemberStatus::ACTIVE,
                'joined_at' => now(),
            ]);

            $invite->update([
                'status' => GroupInvitationStatus::ACCEPTED,
                'responded_at' => now(),
            ]);

            return $destineGroup;
        });
    }

    public function declineInvitation(GroupInvitation $invitation, User $user): GroupInvitation
    {
        return DB::transaction(function () use ($invitation, $user): GroupInvitation {
            $invite = GroupInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if ($invite->status !== GroupInvitationStatus::PENDING) {
                throw new \DomainException('O status do convite não é pendente');
            } elseif ($invite->expires_at->isPast()) {
                throw new \DomainException('O convite está expirado');
            } elseif (mb_strtolower(trim($user->email)) !== mb_strtolower(trim($invite->invited_email))) {
                throw new \DomainException('O email do convite não pertence a esse usuário');
            }

            $invite->update([
                'status' => GroupInvitationStatus::DECLINED,
                'responded_at' => now(),
            ]);

            return $invite;
        });
    }

    public function unlinkCouple(Group $group, User $actor): array
    {
        return DB::transaction(function () use ($group, $actor): array {
            $currentUserGroup = Group::whereKey($group->id)->lockForUpdate()->firstOrFail();
            $activeMemberships = $currentUserGroup->memberships()->where('status', GroupMemberStatus::ACTIVE)->with('user')->get();

            if ($currentUserGroup->status !== GroupStatus::ACTIVE) {
                throw new \DomainException('O grupo está inativo');
            } elseif (! $activeMemberships->contains('user_id', $actor->id)) {
                throw new \DomainException('O usuario não pertence a este grupo');
            } elseif ($activeMemberships->count() !== 2) {
                throw new \DomainException('O grupo não possue 2 integrantes');
            }

            $currentUserGroup->update([
                'status' => GroupStatus::INACTIVE,
            ]);

            $newGroups = [];

            foreach ($activeMemberships as $member) {
                $member->update([
                    'status' => GroupMemberStatus::LEFT,
                    'left_at' => now(),
                ]);

                $newGroups[$member->user_id] = $this->createPersonalGroup($member->user);
            }

            $currentUserGroup->invitations()->where('status', GroupInvitationStatus::PENDING)->update([
                'status' => GroupInvitationStatus::EXPIRED,
            ]);

            return $newGroups;
        });
    }
}
