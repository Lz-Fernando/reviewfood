<?php

namespace App\Models;

use App\Enums\RestaurantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_id', 'category_id', 'created_by_user_id', 'name', 'address', 'notes', 'cover_photo_disk', 'cover_photo_path', 'status', 'archived_at'])]
class Restaurant extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => RestaurantStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(RestaurantPreference::class);
    }
}
