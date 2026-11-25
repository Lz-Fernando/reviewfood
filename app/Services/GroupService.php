<?php

namespace App\Services;

use App\Enums\GroupMemberStatus;
use App\Enums\GroupStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
}
