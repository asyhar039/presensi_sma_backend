<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('api docs ui is available outside production', function (): void {
    $this->get('/docs/api')->assertOk();
});

test('openapi document lists all api endpoints with bearer auth', function (): void {
    $response = $this->getJson('/docs/api.json');

    $response->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
        ->assertJsonStructure(['paths' => ['/auth/login', '/auth/logout', '/auth/me', '/auth/information', '/user']]);

    expect($response->json('paths./auth/login.post.security'))->toBe([]);
    expect($response->json('paths./auth/me.get.tags'))->toContain('Auth');
});

test('view api docs gate allows non-production environments', function (): void {
    expect(app()->isProduction())->toBeFalse()
        ->and(Gate::allows('viewApiDocs'))->toBeTrue();
});
