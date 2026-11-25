<?php

namespace App\Models;

use App\Enums\GroupInvitationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_id', 'invited_by_user_id', 'invited_email', 'token_hash', 'status', 'expires_at', 'responded_at'])]
#[Hidden(['token_hash'])]
class GroupInvitation extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => GroupInvitationStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
