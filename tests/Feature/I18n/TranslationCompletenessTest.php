<?php

declare(strict_types=1);

// @TEST:I18N-001 | SPEC: SPEC-I18N-001.md

namespace Tests\Feature\I18n;

/**
 * 번역 완전성 검증 테스트
 *
 * 목적: 모든 지원 언어(es-MX, en, ko)에서 동일한 번역 키가 존재하는지 검증
 *
 * 검증 범위:
 * - filament.organizations.* (30개 키)
 * - filament.common.actions.* (3개 키)
 */
test('all supported locales have the same organization translation keys', function (): void {
    $supportedLocales = ['es-MX', 'en', 'ko'];
    $requiredKeys = [
        // Resource labels
        'organizations.resource.label',
        'organizations.resource.plural_label',
        'organizations.resource.navigation_label',

        // Form fields
        'organizations.fields.name',
        'organizations.fields.description',
        'organizations.fields.contact_email',
        'organizations.fields.contact_phone',
        'organizations.fields.is_active',

        // Table columns
        'organizations.columns.name',
        'organizations.columns.contact_email',
        'organizations.columns.contact_phone',
        'organizations.columns.is_active',
        'organizations.columns.created_at',
        'organizations.columns.updated_at',

        // Actions
        'organizations.actions.activities',
        'organizations.actions.back',

        // Activities
        'organizations.activities.title',
        'organizations.activities.event_types.created',
        'organizations.activities.event_types.updated',
        'organizations.activities.event_types.deleted',
        'organizations.activities.filters.event_type',
        'organizations.activities.columns.event',
        'organizations.activities.columns.user',
        'organizations.activities.columns.changes',
        'organizations.activities.columns.date',

        // Common actions
        'common.actions.view',
        'common.actions.edit',
        'common.actions.delete',
    ];

    foreach ($supportedLocales as $supportedLocale) {
        foreach ($requiredKeys as $requiredKey) {
            $translationKey = "filament.{$requiredKey}";
            $translation = trans($translationKey, [], $supportedLocale);

            // 번역이 존재하는지 확인 (키 자체가 반환되면 실패)
            expect($translation)
                ->not->toBe($translationKey)
                ->and($translation)
                ->not->toBeEmpty()
                ->and($translation)
                ->toBeString();
        }
    }
});

test('Spanish translations are properly encoded', function (): void {
    app()->setLocale('es-MX');

    // 스페인어 특수문자 확인
    expect(trans('filament.organizations.fields.description'))
        ->toBe('Descripción')
        ->and(trans('filament.organizations.columns.created_at'))
        ->toBe('Fecha de Creación');
});

test('Korean translations use proper terminology', function (): void {
    app()->setLocale('ko');

    // 한국어 번역 확인
    expect(trans('filament.organizations.resource.label'))
        ->toBe('조직')
        ->and(trans('filament.organizations.fields.is_active'))
        ->toBe('활성 상태');
});

test('English translations are concise', function (): void {
    app()->setLocale('en');

    // 영어 번역 확인
    expect(trans('filament.organizations.columns.contact_email'))
        ->toBe('Email')
        ->and(trans('filament.common.actions.view'))
        ->toBe('View');
});

test('all locales have consistent key structure', function (): void {
    $supportedLocales = ['es-MX', 'en', 'ko'];

    foreach ($supportedLocales as $supportedLocale) {
        $translations = require base_path("lang/{$supportedLocale}/filament.php");

        // 필수 섹션 존재 확인
        expect($translations)
            ->toHaveKey('organizations')
            ->toHaveKey('common');

        // organizations 섹션 구조 확인
        expect($translations['organizations'])
            ->toHaveKey('resource')
            ->toHaveKey('fields')
            ->toHaveKey('columns')
            ->toHaveKey('actions')
            ->toHaveKey('activities');

        // common 섹션 구조 확인
        expect($translations['common'])
            ->toHaveKey('actions');
    }
});

test('no duplicate top-level translation keys exist', function (): void {
    $supportedLocales = ['es-MX', 'en', 'ko'];

    foreach ($supportedLocales as $supportedLocale) {
        $translations = require base_path("lang/{$supportedLocale}/filament.php");

        // 최상위 키만 검사 (platform, system, organization, brand, store, organizations, common)
        $topLevelKeys = array_keys($translations);
        $uniqueKeys = array_unique($topLevelKeys);

        expect(count($topLevelKeys))
            ->toBe(count($uniqueKeys))
            ->and($topLevelKeys)
            ->toContain('organizations')
            ->toContain('common');
    }
});
