<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\GroupStatus;

#[Fillable(['name'])]
class Group extends Model
{
    use HasFactory, HasUuids;

    protected $attributes = [
        'status' => 'ACTIVE',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GroupStatus::class,
        ];
    }
}
