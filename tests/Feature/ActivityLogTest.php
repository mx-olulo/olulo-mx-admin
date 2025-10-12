<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

describe('Organization Activity Logging', function () {
    test('organization creation is logged', function () {
        $organization = Organization::create([
            'name' => 'Test Organization',
            'description' => 'Test Description',
            'contact_email' => 'test@example.com',
            'contact_phone' => '+52 123 456 7890',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'ORG', // morph map uses ScopeType enum value
            'subject_id' => $organization->id,
            'description' => 'created',
            'log_name' => 'organization',
        ]);

        $activity = Activity::where('subject_id', $organization->id)
            ->where('subject_type', 'ORG')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->description)->toBe('created');
        expect($activity->properties->toArray())->toHaveKey('attributes');
    })->group('activity-log', 'organization');

    test('organization update is logged', function () {
        $organization = Organization::create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'is_active' => true,
        ]);

        $organization->update([
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $activity = Activity::where('subject_id', $organization->id)
            ->where('subject_type', 'ORG')
            ->where('description', 'updated')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['attributes']['name'])->toBe('Updated Name');
        expect($activity->properties['old']['name'])->toBe('Original Name');
    })->group('activity-log', 'organization');

    test('only dirty attributes are logged', function () {
        $organization = Organization::create([
            'name' => 'Test Organization',
            'description' => 'Test Description',
            'is_active' => true,
        ]);

        // Clear previous activities
        Activity::where('subject_id', $organization->id)->delete();

        // Update only name
        $organization->update([
            'name' => 'New Name',
        ]);

        $activity = Activity::where('subject_id', $organization->id)
            ->where('subject_type', 'ORG')
            ->where('description', 'updated')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['attributes'])->toHaveKey('name');
        expect($activity->properties['attributes'])->not->toHaveKey('description');
    })->group('activity-log', 'organization');
});

describe('User Activity Logging', function () {
    test('user changes are logged', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user->update([
            'name' => 'Jane Doe',
        ]);

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->where('description', 'updated')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['attributes']['name'])->toBe('Jane Doe');
        expect($activity->properties['old']['name'])->toBe('John Doe');
    })->group('activity-log', 'user');

    test('activity has causer when authenticated', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::create([
            'name' => 'Test Organization',
            'is_active' => true,
        ]);

        $activity = Activity::where('subject_id', $organization->id)
            ->where('subject_type', 'ORG')
            ->first();

        expect($activity->causer)->not->toBeNull();
        expect($activity->causer_id)->toBe($user->id);
    })->group('activity-log', 'causer');
});
