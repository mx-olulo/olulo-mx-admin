<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

/**
 * ScopeContextService
 *
 * 현재 사용자의 활성 스코프 컨텍스트를 일원화하여 관리하는 서비스.
 * 세션 기반으로 스코프 정보를 저장/조회/검증.
 *
 * 사용처:
 * - CurrentScopeResolver: team_id 반환
 * - SetScopeContext 미들웨어: 컨텍스트 검증
 * - Filament UI: 스코프 스위처
 * - 컨트롤러/서비스: 현재 스코프 조회
 */
class ScopeContextService
{
    /**
     * 세션 키 상수
     */
    private const SESSION_SCOPE_TYPE = 'current_scope_type';

    private const SESSION_SCOPE_ID = 'current_scope_id';

    /**
     * 현재 활성 스코프 타입 반환
     *
     * @return string|null 'ORG'|'BRAND'|'STORE'|null
     */
    public function getCurrentScopeType(): ?string
    {
        return Session::get(self::SESSION_SCOPE_TYPE);
    }

    /**
     * 현재 활성 스코프 ID 반환 (실제 엔터티 PK)
     */
    public function getCurrentScopeId(): ?int
    {
        return Session::get(self::SESSION_SCOPE_ID);
    }

    /**
     * 현재 활성 스코프의 team_id 반환 (scopes.id)
     */
    public function getCurrentTeamId(): ?int
    {
        $scopeType = $this->getCurrentScopeType();
        $scopeId = $this->getCurrentScopeId();

        if (! $scopeType || ! $scopeId) {
            return null;
        }

        // scopes 테이블 조회
        $scope = \App\Models\Scope::where('scope_type', $scopeType)
            ->where('scope_ref_id', $scopeId)
            ->first();

        return $scope?->id;
    }

    /**
     * 스코프 컨텍스트 설정
     *
     * @param  string  $scopeType  'ORG'|'BRAND'|'STORE'
     * @param  int  $scopeId  실제 엔터티 PK
     * @param  int|null  $teamId  scopes.id (선택, 없으면 자동 조회)
     *
     * @throws \InvalidArgumentException 유효하지 않은 스코프 타입
     */
    public function setScope(string $scopeType, int $scopeId, ?int $teamId = null): void
    {
        // 스코프 타입 검증
        if (! in_array($scopeType, \App\Models\Scope::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid scope type: {$scopeType}");
        }

        Session::put([
            self::SESSION_SCOPE_TYPE => $scopeType,
            self::SESSION_SCOPE_ID => $scopeId,
        ]);

        // team_id 조회 후 Spatie에 설정
        $resolvedTeamId = $teamId ?? $this->getCurrentTeamId();
        if ($resolvedTeamId !== null) {
            setPermissionsTeamId($resolvedTeamId);
        }
    }

    /**
     * 스코프 컨텍스트 초기화 (로그아웃 또는 권한 상실 시)
     */
    public function clearScope(): void
    {
        Session::forget([
            self::SESSION_SCOPE_TYPE,
            self::SESSION_SCOPE_ID,
        ]);

        // Spatie Permission team_id 초기화
        setPermissionsTeamId(null);
    }

    /**
     * 현재 컨텍스트가 설정되어 있는지 확인
     */
    public function hasScope(): bool
    {
        return $this->getCurrentScopeType() !== null
            && $this->getCurrentScopeId() !== null;
    }

    /**
     * 현재 컨텍스트 정보를 배열로 반환
     *
     * @return array{type: string|null, id: int|null, team_id: int|null}
     */
    public function getCurrentScope(): array
    {
        return [
            'type' => $this->getCurrentScopeType(),
            'id' => $this->getCurrentScopeId(),
            'team_id' => $this->getCurrentTeamId(),
        ];
    }

    /**
     * 사용자가 특정 스코프에 접근 권한이 있는지 검증
     */
    public function userCanAccessScope(\App\Models\User $user, string $scopeType, int $scopeId): bool
    {
        // TODO: Membership 모델 구현 후 활성화
        // return $user->memberships()
        //     ->where('scope_type', $scopeType)
        //     ->where('scope_ref_id', $scopeId)
        //     ->exists();

        // 임시: 모든 접근 허용 (개발 단계)
        return true;
    }

    /**
     * 사용자의 첫 번째 멤버십을 기본 컨텍스트로 설정
     *
     * @return bool 설정 성공 여부
     */
    public function setDefaultScopeForUser(\App\Models\User $user): bool
    {
        // TODO: Membership 모델 구현 후 활성화
        // $firstMembership = $user->memberships()->first();
        //
        // if ($firstMembership) {
        //     $this->setScope(
        //         $firstMembership->scope_type,
        //         $firstMembership->scope_ref_id
        //     );
        //     return true;
        // }

        return false;
    }
}
