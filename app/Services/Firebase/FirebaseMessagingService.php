<?php

declare(strict_types=1);

namespace App\Services\Firebase;

use Exception;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Firebase 메시징 서비스
 *
 * Firebase Cloud Messaging(FCM)을 통한 푸시 알림 기능을 담당하는 서비스 클래스입니다.
 * 단일 및 다중 디바이스에 대한 푸시 알림 전송을 지원합니다.
 *
 * 주요 기능:
 * - 단일 디바이스 푸시 알림 전송
 * - 다중 디바이스 푸시 알림 전송 (배치)
 * - 알림 설정 및 데이터 페이로드 관리
 * - 전송 결과 추적 및 로깅
 */
class FirebaseMessagingService
{
    private Messaging $messaging;

    /**
     * Firebase 메시징 서비스 초기화
     *
     * @param  FirebaseClientFactory  $clientFactory  Firebase 클라이언트 팩토리
     */
    public function __construct(private readonly FirebaseClientFactory $clientFactory)
    {
        $this->messaging = $this->clientFactory->createMessaging();
    }

    /**
     * FCM 푸시 알림 전송 (단일 디바이스)
     *
     * 지정된 디바이스 토큰으로 푸시 알림을 전송합니다.
     * 기본 알림과 추가 데이터를 함께 전송할 수 있습니다.
     *
     * @param  string  $deviceToken  FCM 디바이스 토큰
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션 (배지, 사운드 등)
     * @return bool 전송 성공 여부
     */
    public function sendPushNotification(
        string $deviceToken,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): bool {
        try {
            $notification = $this->createNotification($title, $body, $options);

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);

            Log::info('FCM 푸시 알림 전송 완료', [
                'device_token' => $this->maskToken($deviceToken),
                'title' => $title,
                'has_data' => ! empty($data),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('FCM 푸시 알림 전송 실패', [
                'error' => $e->getMessage(),
                'device_token' => $this->maskToken($deviceToken),
                'title' => $title,
            ]);

            return false;
        }
    }

    /**
     * FCM 푸시 알림 전송 (여러 디바이스)
     *
     * 여러 디바이스 토큰에 동일한 알림을 배치로 전송합니다.
     * 각 토큰의 전송 결과를 개별적으로 추적합니다.
     *
     * @param  array<int, string>  $deviceTokens  FCM 디바이스 토큰 배열
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션
     * @return array<string, mixed> 전송 결과 (성공/실패 토큰 리스트)
     */
    public function sendPushNotificationToMultipleDevices(
        array $deviceTokens,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): array {
        try {
            if (empty($deviceTokens)) {
                Log::warning('FCM 다중 푸시 알림: 빈 토큰 배열');

                return [
                    'success_count' => 0,
                    'failure_count' => 0,
                    'success_tokens' => [],
                    'failed_tokens' => [],
                ];
            }

            $notification = $this->createNotification($title, $body, $options);

            $message = CloudMessage::new()->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $sendReport = $this->messaging->sendMulticast($message, $deviceTokens);

            $results = [
                'success_count' => $sendReport->successes()->count(),
                'failure_count' => $sendReport->failures()->count(),
                'success_tokens' => [],
                'failed_tokens' => [],
            ];

            /** @phpstan-ignore-next-line MulticastSendReport 타입 정의 불완전성 */
            foreach ($sendReport->successes() as $result) {
                $results['success_tokens'][] = $result->target()->value();
            }

            /** @phpstan-ignore-next-line MulticastSendReport 타입 정의 불완전성 */
            foreach ($sendReport->failures() as $result) {
                $results['failed_tokens'][] = [
                    'token' => $result->target()->value(),
                    'error' => $result->error()->getMessage(),
                ];
            }

            Log::info('FCM 다중 푸시 알림 전송 완료', [
                'total_tokens' => count($deviceTokens),
                'success_count' => $results['success_count'],
                'failure_count' => $results['failure_count'],
                'title' => $title,
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('FCM 다중 푸시 알림 전송 실패', [
                'error' => $e->getMessage(),
                'token_count' => count($deviceTokens),
                'title' => $title,
            ]);

            return [
                'success_count' => 0,
                'failure_count' => count($deviceTokens),
                'success_tokens' => [],
                'failed_tokens' => array_map(function ($token) use ($e) {
                    return [
                        'token' => $token,
                        'error' => $e->getMessage(),
                    ];
                }, $deviceTokens),
            ];
        }
    }

    /**
     * 주제(Topic)로 푸시 알림 전송
     *
     * 특정 주제를 구독한 모든 디바이스에 알림을 전송합니다.
     *
     * @param  string  $topic  주제 이름
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션
     * @return bool 전송 성공 여부
     */
    public function sendPushNotificationToTopic(
        string $topic,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): bool {
        try {
            $notification = $this->createNotification($title, $body, $options);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);

            Log::info('FCM 주제 푸시 알림 전송 완료', [
                'topic' => $topic,
                'title' => $title,
                'has_data' => ! empty($data),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('FCM 주제 푸시 알림 전송 실패', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'title' => $title,
            ]);

            return false;
        }
    }

    /**
     * 디바이스 토큰을 주제에 구독
     *
     * @param  array<int, string>|string  $deviceTokens  디바이스 토큰(들)
     * @param  string  $topic  구독할 주제
     * @return array<string, mixed> 구독 결과
     */
    public function subscribeToTopic(array|string $deviceTokens, string $topic): array
    {
        try {
            $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];

            // Kreait SDK의 TopicManagementResponse 타입 정의 누락으로 인한 타입 체크 우회
            $response = $this->messaging->subscribeToTopic($topic, $tokens);

            Log::info('FCM 주제 구독 완료', [
                'topic' => $topic,
                'token_count' => count($tokens),
                'success_count' => $response->successes()->count(), // @phpstan-ignore-line
                'failure_count' => $response->failures()->count(), // @phpstan-ignore-line
            ]);

            return [
                'success_count' => $response->successes()->count(), // @phpstan-ignore-line
                'failure_count' => $response->failures()->count(), // @phpstan-ignore-line
                'errors' => array_map(fn ($error) => $error->error()->getMessage(), $response->failures()->getItems()), // @phpstan-ignore-line
            ];
        } catch (Exception $e) {
            Log::error('FCM 주제 구독 실패', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);

            return [
                'success_count' => 0,
                'failure_count' => is_array($deviceTokens) ? count($deviceTokens) : 1,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * 디바이스 토큰을 주제에서 구독 해제
     *
     * @param  array<int, string>|string  $deviceTokens  디바이스 토큰(들)
     * @param  string  $topic  구독 해제할 주제
     * @return array<string, mixed> 구독 해제 결과
     */
    public function unsubscribeFromTopic(array|string $deviceTokens, string $topic): array
    {
        try {
            $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];

            // Kreait SDK의 TopicManagementResponse 타입 정의 누락으로 인한 타입 체크 우회
            $response = $this->messaging->unsubscribeFromTopic($topic, $tokens);

            Log::info('FCM 주제 구독 해제 완료', [
                'topic' => $topic,
                'token_count' => count($tokens),
                'success_count' => $response->successes()->count(), // @phpstan-ignore-line
                'failure_count' => $response->failures()->count(), // @phpstan-ignore-line
            ]);

            return [
                'success_count' => $response->successes()->count(), // @phpstan-ignore-line
                'failure_count' => $response->failures()->count(), // @phpstan-ignore-line
                'errors' => array_map(fn ($error) => $error->error()->getMessage(), $response->failures()->getItems()), // @phpstan-ignore-line
            ];
        } catch (Exception $e) {
            Log::error('FCM 주제 구독 해제 실패', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);

            return [
                'success_count' => 0,
                'failure_count' => is_array($deviceTokens) ? count($deviceTokens) : 1,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * 알림 객체 생성
     *
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $options  추가 알림 옵션
     * @return Notification Firebase 알림 객체
     */
    private function createNotification(string $title, string $body, ?array $options = null): Notification
    {
        $notification = Notification::create($title, $body);

        if ($options) {
            if (isset($options['image_url'])) {
                $notification = $notification->withImageUrl($options['image_url']);
            }

            // 추가 옵션들은 CloudMessage의 config에서 처리
        }

        return $notification;
    }

    /**
     * 디바이스 토큰 마스킹 (보안용)
     *
     * 로그에 기록할 때 토큰의 일부만 표시하여 보안을 강화합니다.
     *
     * @param  string  $token  원본 토큰
     * @return string 마스킹된 토큰
     */
    private function maskToken(string $token): string
    {
        return substr($token, 0, 20) . '...';
    }
}
