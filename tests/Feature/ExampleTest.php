<?php

declare(strict_types=1);

// use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * A basic test example.
 */
test('the application returns a successful response', function () {
    $response = $this->get('/');

    expect($response->status())->toBe(200);
});
