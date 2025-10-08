<?php

namespace App\Permissions;

use App\Services\ScopeContextService;

/**
 * CurrentScopeResolver
 * 
 * Spatie Permission Teams 기능을 위한 커스텀 team_resolver.
 * ScopeContextService를 통해 세션 기반 스코프 컨텍스트를 조회하여 team_id 반환.
 * 
 * 동작 방식:
 * 1. ScopeContextService에서 현재 활성 스코프 조회 (세션 기반)
 * 2. scopes 테이블에서 해당 스코프의 team_id(scopes.id) 반환
 * 3. 컨텍스트가 없으면 null 반환 (글로벌 권한)
 * 
 * @see config/permission.php 'team_resolver'
 * @see \App\Services\ScopeContextService
 */
class CurrentScopeResolver
{
    /**
     * @var ScopeContextService
     */
    private ScopeContextService $scopeContext;

    public function __construct(ScopeContextService $scopeContext)
    {
        $this->scopeContext = $scopeContext;
    }

    /**
     * Spatie Permission이 호출하는 메서드.
     * 현재 요청의 활성 스코프(team_id)를 반환.
     * 
     * @return int|null scopes.id 또는 null(글로벌 컨텍스트)
     */
    public function __invoke(): ?int
    {
        return $this->scopeContext->getCurrentTeamId();
    }
}
