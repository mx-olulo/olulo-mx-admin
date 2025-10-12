<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_type');
            $table->unsignedBigInteger('tenant_id');
            $table->string('scope_type');
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_type', 'tenant_id', 'scope_type'], 'teams_tenant_unique');
            $table->index('scope_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
