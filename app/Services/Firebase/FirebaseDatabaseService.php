<?php

declare(strict_types=1);

namespace App\Services\Firebase;

use Exception;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;

/**
 * Firebase Realtime Database 서비스
 *
 * Firebase Realtime Database 관련 기능을 담당하는 서비스 클래스입니다.
 * 실시간 데이터 저장, 조회, 업데이트, 삭제 등의 기능을 제공합니다.
 *
 * 주요 기능:
 * - 실시간 데이터 CRUD operations
 * - 경로 기반 데이터 관리
 * - 트랜잭션 지원
 * - 리스너 및 이벤트 관리
 */
class FirebaseDatabaseService
{
    private Database $database;

    /**
     * Firebase Database 서비스 초기화
     *
     * @param  FirebaseClientFactory  $clientFactory  Firebase 클라이언트 팩토리
     */
    public function __construct(private readonly FirebaseClientFactory $clientFactory)
    {
        $this->database = $this->clientFactory->createDatabase();
    }

    /**
     * Realtime Database에 데이터 저장
     *
     * 지정된 경로에 데이터를 저장합니다. 기존 데이터가 있으면 덮어씁니다.
     *
     * @param  string  $path  데이터 경로
     * @param  mixed  $data  저장할 데이터
     * @return bool 저장 성공 여부
     */
    public function setRealtimeData(string $path, mixed $data): bool
    {
        try {
            $this->database->getReference($path)->set($data);

            Log::info('Realtime Database 데이터 저장 완료', [
                'path' => $path,
                'data_type' => gettype($data),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 저장 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Realtime Database에서 데이터 조회
     *
     * 지정된 경로의 데이터를 조회합니다.
     *
     * @param  string  $path  데이터 경로
     * @return mixed 조회된 데이터 또는 null
     */
    public function getRealtimeData(string $path): mixed
    {
        try {
            $snapshot = $this->database->getReference($path)->getSnapshot();

            Log::info('Realtime Database 데이터 조회 완료', [
                'path' => $path,
                'has_data' => $snapshot->exists(),
            ]);

            return $snapshot->getValue();
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 조회 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Realtime Database에서 데이터 업데이트
     *
     * 지정된 경로의 특정 필드만 업데이트합니다.
     *
     * @param  string  $path  데이터 경로
     * @param  array<string, mixed>  $updates  업데이트할 필드들
     * @return bool 업데이트 성공 여부
     */
    public function updateRealtimeData(string $path, array $updates): bool
    {
        try {
            $this->database->getReference($path)->update($updates);

            Log::info('Realtime Database 데이터 업데이트 완료', [
                'path' => $path,
                'updated_fields' => array_keys($updates),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 업데이트 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
                'updates' => $updates,
            ]);

            return false;
        }
    }

    /**
     * Realtime Database에서 데이터 삭제
     *
     * 지정된 경로의 데이터를 삭제합니다.
     *
     * @param  string  $path  데이터 경로
     * @return bool 삭제 성공 여부
     */
    public function deleteRealtimeData(string $path): bool
    {
        try {
            $this->database->getReference($path)->remove();

            Log::info('Realtime Database 데이터 삭제 완료', ['path' => $path]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 삭제 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Realtime Database에 새로운 자식 노드 추가
     *
     * 지정된 경로에 고유한 키를 가진 새 자식 노드를 추가합니다.
     *
     * @param  string  $path  부모 노드 경로
     * @param  mixed  $data  추가할 데이터
     * @return string|null 생성된 키 또는 null (실패 시)
     */
    public function pushRealtimeData(string $path, mixed $data): ?string
    {
        try {
            $reference = $this->database->getReference($path)->push($data);
            $key = $reference->getKey();

            Log::info('Realtime Database 데이터 푸시 완료', [
                'path' => $path,
                'generated_key' => $key,
            ]);

            return $key;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 푸시 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Realtime Database 트랜잭션 실행
     *
     * 원자적 업데이트를 위한 트랜잭션을 실행합니다.
     *
     * @param  string  $path  트랜잭션 경로
     * @param  callable  $updateFunction  업데이트 함수
     * @return bool 트랜잭션 성공 여부
     */
    public function runTransaction(string $path, callable $updateFunction): bool
    {
        try {
            /** @phpstan-ignore-next-line transaction() 메서드는 Kreait SDK에서 제공되나 타입 정의 누락 */
            $this->database->getReference($path)->transaction($updateFunction);

            Log::info('Realtime Database 트랜잭션 완료', ['path' => $path]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 트랜잭션 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 데이터 존재 여부 확인
     *
     * 지정된 경로에 데이터가 존재하는지 확인합니다.
     *
     * @param  string  $path  확인할 경로
     * @return bool 데이터 존재 여부
     */
    public function exists(string $path): bool
    {
        try {
            $snapshot = $this->database->getReference($path)->getSnapshot();

            return $snapshot->exists();
        } catch (Exception $e) {
            Log::error('Realtime Database 존재 확인 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 자식 노드 개수 조회
     *
     * 지정된 경로의 자식 노드 개수를 반환합니다.
     *
     * @param  string  $path  확인할 경로
     * @return int 자식 노드 개수
     */
    public function getChildrenCount(string $path): int
    {
        try {
            $snapshot = $this->database->getReference($path)->getSnapshot();

            return $snapshot->numChildren();
        } catch (Exception $e) {
            Log::error('Realtime Database 자식 노드 개수 조회 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 정렬된 데이터 조회
     *
     * 지정된 필드로 정렬된 데이터를 조회합니다.
     *
     * @param  string  $path  데이터 경로
     * @param  string  $orderBy  정렬 기준 필드
     * @param  int|null  $limit  조회할 개수 제한
     * @param  bool  $desc  내림차순 정렬 여부
     * @return array<string, mixed> 정렬된 데이터 배열
     */
    public function getOrderedData(string $path, string $orderBy, ?int $limit = null, bool $desc = false): array
    {
        try {
            $query = $this->database->getReference($path)->orderByChild($orderBy);

            if ($limit !== null) {
                $query = $desc ? $query->limitToLast($limit) : $query->limitToFirst($limit);
            }

            $snapshot = $query->getSnapshot();

            Log::info('Realtime Database 정렬된 데이터 조회 완료', [
                'path' => $path,
                'order_by' => $orderBy,
                'limit' => $limit,
                'desc' => $desc,
                'result_count' => $snapshot->numChildren(),
            ]);

            return $snapshot->getValue() ?? [];
        } catch (Exception $e) {
            Log::error('Realtime Database 정렬된 데이터 조회 실패', [
                'path' => $path,
                'order_by' => $orderBy,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 범위 기반 데이터 조회
     *
     * 지정된 값 범위 내의 데이터를 조회합니다.
     *
     * @param  string  $path  데이터 경로
     * @param  string  $orderBy  정렬 기준 필드
     * @param  mixed  $startAt  시작 값
     * @param  mixed  $endAt  종료 값
     * @return array<string, mixed> 범위 내 데이터 배열
     */
    public function getDataInRange(string $path, string $orderBy, mixed $startAt, mixed $endAt): array
    {
        try {
            $query = $this->database->getReference($path)
                ->orderByChild($orderBy)
                ->startAt($startAt)
                ->endAt($endAt);

            $snapshot = $query->getSnapshot();

            Log::info('Realtime Database 범위 데이터 조회 완료', [
                'path' => $path,
                'order_by' => $orderBy,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'result_count' => $snapshot->numChildren(),
            ]);

            return $snapshot->getValue() ?? [];
        } catch (Exception $e) {
            Log::error('Realtime Database 범위 데이터 조회 실패', [
                'path' => $path,
                'order_by' => $orderBy,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 서버 타임스탬프 생성
     *
     * Firebase 서버의 현재 타임스탬프를 반환합니다.
     *
     * @return array<string, string> 서버 타임스탬프 플레이스홀더
     */
    public function getServerTimestamp(): array
    {
        return ['.sv' => 'timestamp'];
    }
}
