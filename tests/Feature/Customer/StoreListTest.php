<?php

// @TEST:STORE-LIST-001 | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 고객 상점 목록 페이지 테스트
 *
 * SPEC-STORE-LIST-001: 활성 Store만 목록 표시, N+1 쿼리 방지
 */
class StoreListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 활성 Store만 목록에 표시되는지 테스트
     */
    public function test_only_active_stores_are_displayed(): void
    {
        // Given: 활성/비활성 Store 생성
        $organization = Organization::factory()->create(['name' => 'Test Org']);

        $activeStore = Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Active Store',
            'is_active' => true,
        ]);

        Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Inactive Store',
            'is_active' => false,
        ]);

        // When: 홈 페이지 접근
        $testResponse = $this->get('/');

        // Then: 활성 Store만 표시
        $testResponse
            ->assertStatus(200)
            ->assertInertia(
                fn (Assert $assert): \Inertia\Testing\AssertableInertia => $assert
                    ->component('Customer/Home')
                    ->has('stores.data', 1)
                    ->where('stores.data.0.id', $activeStore->id)
                    ->where('stores.data.0.name', 'Active Store')
            );
    }

    /**
     * Organization Eager Loading 테스트
     */
    public function test_organization_is_eager_loaded(): void
    {
        // Given: Organization과 Store 생성
        $organization = Organization::factory()->create(['name' => 'Olulo Korea']);

        Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Test Store',
            'is_active' => true,
        ]);

        // When: 홈 페이지 접근
        $testResponse = $this->get('/');

        // Then: Organization 데이터 포함
        $testResponse
            ->assertStatus(200)
            ->assertInertia(
                fn (Assert $assert): \Inertia\Testing\AssertableInertia => $assert
                    ->component('Customer/Home')
                    ->has('stores.data.0.organization')
                    ->where('stores.data.0.organization.name', 'Olulo Korea')
            );
    }

    /**
     * 페이지네이션 동작 테스트
     */
    public function test_stores_are_paginated(): void
    {
        // Given: 15개 Store 생성 (기본 10개/페이지)
        $organization = Organization::factory()->create();

        Store::factory()->count(15)->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        // When: 1페이지 접근
        $testResponse = $this->get('/');

        // Then: 10개만 표시, 페이지네이션 정보 포함
        $testResponse
            ->assertStatus(200)
            ->assertInertia(
                fn (Assert $assert): \Inertia\Testing\AssertableInertia => $assert
                    ->component('Customer/Home')
                    ->has('stores.data', 10)
                    ->where('stores.current_page', 1)
                    ->where('stores.per_page', 10)
                    ->where('stores.total', 15)
            );
    }

    /**
     * N+1 쿼리 방지 테스트 (≤3회: stores + organizations + count)
     */
    public function test_no_n_plus_one_queries(): void
    {
        // Given: 10개 Store 생성
        $organization = Organization::factory()->create();

        Store::factory()->count(10)->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        // When/Then: 쿼리 수 검증 (stores, organizations eager load, pagination count)
        $this->assertQueryCount(3, function (): void {
            $this->get('/');
        });
    }

    /**
     * 빈 상태 테스트
     */
    public function test_empty_state_when_no_stores(): void
    {
        // When: Store가 없는 상태에서 접근
        $testResponse = $this->get('/');

        // Then: 빈 배열 반환
        $testResponse
            ->assertStatus(200)
            ->assertInertia(
                fn (Assert $assert): \Inertia\Testing\AssertableInertia => $assert
                    ->component('Customer/Home')
                    ->has('stores.data', 0)
                    ->where('stores.total', 0)
            );
    }

    /**
     * 쿼리 수를 계산하는 헬퍼 메서드
     */
    private function assertQueryCount(int $expected, callable $callback): void
    {
        $queryCount = 0;

        \DB::listen(function ($query) use (&$queryCount): void {
            $queryCount++;
        });

        $callback();

        $this->assertEquals($expected, $queryCount, "Expected {$expected} queries, but {$queryCount} were executed.");
    }
}
