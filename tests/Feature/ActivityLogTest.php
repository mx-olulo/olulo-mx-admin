<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_creation_is_logged(): void
    {
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

        $this->assertNotNull($activity);
        $this->assertEquals('created', $activity->description);
        $this->assertArrayHasKey('attributes', $activity->properties->toArray());
    }

    public function test_organization_update_is_logged(): void
    {
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

        $this->assertNotNull($activity);
        $this->assertEquals('Updated Name', $activity->properties['attributes']['name']);
        $this->assertEquals('Original Name', $activity->properties['old']['name']);
    }

    public function test_only_dirty_attributes_are_logged(): void
    {
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

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('name', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('description', $activity->properties['attributes']);
    }

    public function test_user_changes_are_logged(): void
    {
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

        $this->assertNotNull($activity);
        $this->assertEquals('Jane Doe', $activity->properties['attributes']['name']);
        $this->assertEquals('John Doe', $activity->properties['old']['name']);
    }

    public function test_activity_has_causer_when_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::create([
            'name' => 'Test Organization',
            'is_active' => true,
        ]);

        $activity = Activity::where('subject_id', $organization->id)
            ->where('subject_type', 'ORG')
            ->first();

        $this->assertNotNull($activity->causer);
        $this->assertEquals($user->id, $activity->causer_id);
    }
}
