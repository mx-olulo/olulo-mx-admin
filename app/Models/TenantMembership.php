<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TenantMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_type',
        'tenant_id',
        'team_id',
        'scope_type',
        'role_key',
        'is_owner',
        'status',
        'meta',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'tenant_type', 'tenant_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
