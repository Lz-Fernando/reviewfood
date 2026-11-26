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

        if (!$group->memberships()->where('user_id', $inviter->id)->where('status', GroupMemberStatus::ACTIVE)->exists()) {
            throw new \DomainException('O usuário não pertence a esse grupo');
        }

        if ($group->memberships()->where('status', GroupMemberStatus::ACTIVE)->count() >= 2) {
            throw new \DomainException("O grupo já possui 2 usuários");
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
}
