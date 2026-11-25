<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visit_id', 'user_id', 'food', 'environment', 'service', 'value_for_money', 'comment', 'status', 'completed_at'])]
class Review extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'food' => 'integer',
            'environment' => 'integer',
            'service' => 'integer',
            'value_for_money' => 'integer',
            'status' => ReviewStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
