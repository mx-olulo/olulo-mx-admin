<?php

declare(strict_types=1);

// @TEST:I18N-001 | SPEC: SPEC-I18N-001.md

namespace Tests\Feature\Filament;

use App\Filament\Organization\Resources\Organizations\OrganizationResource;

/**
 * OrganizationResource 다국어 테스트
 *
 * 목적: 번역 파일과 Resource 메서드가 올바르게 연동되는지 검증
 *
 * 검증 범위:
 * - Resource 라벨 (네비게이션, 모델명)
 * - 폼 필드 라벨
 * - 테이블 컬럼 라벨
 * - 액션 라벨
 */
test('navigation label returns translated text for es-MX', function (): void {
    app()->setLocale('es-MX');

    expect(OrganizationResource::getNavigationLabel())
        ->toBe('Organizaciones');
});

test('navigation label returns translated text for en', function (): void {
    app()->setLocale('en');

    expect(OrganizationResource::getNavigationLabel())
        ->toBe('Organizations');
});

test('navigation label returns translated text for ko', function (): void {
    app()->setLocale('ko');

    expect(OrganizationResource::getNavigationLabel())
        ->toBe('조직');
});

test('model label returns translated text for es-MX', function (): void {
    app()->setLocale('es-MX');

    expect(OrganizationResource::getModelLabel())
        ->toBe('Organización');
});

test('model label returns translated text for en', function (): void {
    app()->setLocale('en');

    expect(OrganizationResource::getModelLabel())
        ->toBe('Organization');
});

test('model label returns translated text for ko', function (): void {
    app()->setLocale('ko');

    expect(OrganizationResource::getModelLabel())
        ->toBe('조직');
});

test('plural model label returns translated text for es-MX', function (): void {
    app()->setLocale('es-MX');

    expect(OrganizationResource::getPluralModelLabel())
        ->toBe('Organizaciones');
});

test('plural model label returns translated text for en', function (): void {
    app()->setLocale('en');

    expect(OrganizationResource::getPluralModelLabel())
        ->toBe('Organizations');
});

test('plural model label returns translated text for ko', function (): void {
    app()->setLocale('ko');

    expect(OrganizationResource::getPluralModelLabel())
        ->toBe('조직');
});

test('form field labels use translation keys', function (): void {
    app()->setLocale('es-MX');

    // name 필드
    expect(trans('filament.organizations.fields.name'))
        ->toBe('Nombre');

    // contact_email 필드
    expect(trans('filament.organizations.fields.contact_email'))
        ->toBe('Correo Electrónico de Contacto');

    // is_active 필드
    expect(trans('filament.organizations.fields.is_active'))
        ->toBe('Estado Activo');
});

test('table column labels use translation keys', function (): void {
    app()->setLocale('en');

    // name 컬럼
    expect(trans('filament.organizations.columns.name'))
        ->toBe('Name');

    // contact_email 컬럼
    expect(trans('filament.organizations.columns.contact_email'))
        ->toBe('Email');

    // is_active 컬럼
    expect(trans('filament.organizations.columns.is_active'))
        ->toBe('Active');
});

test('action labels use translation keys', function (): void {
    app()->setLocale('ko');

    // 공통 액션
    expect(trans('filament.common.actions.view'))
        ->toBe('보기');

    expect(trans('filament.common.actions.edit'))
        ->toBe('수정');

    expect(trans('filament.common.actions.delete'))
        ->toBe('삭제');

    // 활동 로그 액션
    expect(trans('filament.organizations.actions.activities'))
        ->toBe('활동 로그');
});

test('activity log labels use translation keys', function (): void {
    app()->setLocale('es-MX');

    // 페이지 제목
    expect(trans('filament.organizations.activities.title', ['name' => 'Test Org']))
        ->toBe('Registro de Actividades: Test Org');

    // 이벤트 타입
    expect(trans('filament.organizations.activities.event_types.created'))
        ->toBe('Creado');

    expect(trans('filament.organizations.activities.event_types.updated'))
        ->toBe('Actualizado');

    // 필터 라벨
    expect(trans('filament.organizations.activities.filters.event_type'))
        ->toBe('Tipo de Evento');

    // 컬럼 라벨
    expect(trans('filament.organizations.activities.columns.event'))
        ->toBe('Evento');

    expect(trans('filament.organizations.activities.columns.user'))
        ->toBe('Usuario');
});

test('locale changes immediately reflect in translations', function (): void {
    // 영어로 설정
    app()->setLocale('en');
    expect(OrganizationResource::getNavigationLabel())
        ->toBe('Organizations');

    // 스페인어로 변경
    app()->setLocale('es-MX');
    expect(OrganizationResource::getNavigationLabel())
        ->toBe('Organizaciones');

    // 한국어로 변경
    app()->setLocale('ko');
    expect(OrganizationResource::getNavigationLabel())
        ->toBe('조직');
});
