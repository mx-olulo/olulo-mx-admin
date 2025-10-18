<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// Custom expectations for Laravel
expect()->extend('toBeAuthenticated', function () {
    expect(auth()->check())->toBeTrue();

    return $this;
});

expect()->extend('toBeGuest', function () {
    expect(auth()->guest())->toBeTrue();

    return $this;
});

expect()->extend('toHaveModel', function (string $model) {
    expect($this->value)->toBeInstanceOf($model);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a test user with optional attributes.
 */
function testUser(array $attributes = []): \App\Models\User
{
    return \App\Models\User::factory()->create($attributes);
}

/**
 * Act as the given user.
 */
function actingAs(\App\Models\User $user): \Illuminate\Foundation\Testing\TestCase
{
    return test()->actingAs($user);
}
