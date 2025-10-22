<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => null, // 기본값은 null, 필요 시 Brand로 덮어쓰기
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true) . ' Store',
            'description' => fake()->optional()->text(200),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the store belongs to a brand.
     */
    public function forBrand(?int $brandId = null): static
    {
        return $this->state(function (array $attributes) use ($brandId) {
            $brand = $brandId ? Brand::find($brandId) : Brand::factory()->create();

            return [
                'brand_id' => $brand->id,
                'organization_id' => $brand->organization_id,
            ];
        });
    }

    /**
     * Indicate that the store is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
