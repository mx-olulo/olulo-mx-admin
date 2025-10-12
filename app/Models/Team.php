<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_type',
        'tenant_id',
        'scope_type',
        'name',
    ];

    public function tenant(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'tenant_type', 'tenant_id');
    }
}
