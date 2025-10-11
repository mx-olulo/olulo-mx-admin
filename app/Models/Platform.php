<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 다형 관계: Platform 스코프를 가진 Role들
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'scopeable', 'scope_type', 'scope_ref_id');
    }
}
