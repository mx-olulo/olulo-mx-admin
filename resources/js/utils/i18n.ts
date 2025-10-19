// @CODE:STORE-LIST-001:INFRA | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

/**
 * i18n 유틸리티
 *
 * vite-plugin-laravel-translations를 활용한 다국어 지원
 * - Laravel 번역 파일 자동 로드 (lang/*)
 * - 중첩 키 지원 (customer.home.title)
 * - 기본 로케일: ko (한국어)
 */

type Translations = Record<string, any>;

/**
 * 번역 데이터 가져오기
 *
 * vite-plugin-laravel-translations가 import.meta.env에 주입한 전역 번역 객체
 */
function getTranslations(): Translations {
    if (typeof import.meta.env.VITE_LARAVEL_TRANSLATIONS === 'undefined') {
        console.warn('VITE_LARAVEL_TRANSLATIONS not found. Using empty translations.');
        return {};
    }
    return import.meta.env.VITE_LARAVEL_TRANSLATIONS as Translations;
}

/**
 * 번역 키로 값 가져오기
 *
 * @param key - 번역 키 (예: 'customer.home.title')
 * @param locale - 로케일 (기본: 'ko')
 * @returns 번역된 문자열 또는 키 자체 (번역 없을 경우)
 *
 * @example
 * ```tsx
 * import { trans } from '@/utils/i18n';
 *
 * // 한국어 (기본)
 * trans('customer.home.title') // '상점 목록'
 *
 * // 영어
 * trans('customer.home.title', 'en') // 'Store List'
 *
 * // 스페인어 (멕시코)
 * trans('customer.home.title', 'es-MX') // 'Lista de Tiendas'
 * ```
 */
export function trans(key: string, locale: string = 'ko'): string {
    const translations = getTranslations();

    // 로케일 데이터 확인
    if (!translations[locale]) {
        console.warn(`Locale '${locale}' not found. Falling back to key.`);
        return key;
    }

    // 중첩 키 파싱 (예: 'customer.home.title' → ['customer', 'home', 'title'])
    const keys = key.split('.');
    let value: any = translations[locale];

    for (const k of keys) {
        if (value && typeof value === 'object' && k in value) {
            value = value[k];
        } else {
            // 키를 찾을 수 없으면 원본 키 반환
            console.warn(`Translation key '${key}' not found for locale '${locale}'.`);
            return key;
        }
    }

    return typeof value === 'string' ? value : key;
}

/**
 * 현재 로케일 가져오기
 *
 * @returns 현재 브라우저 로케일 또는 기본값 'ko'
 *
 * @example
 * ```tsx
 * const currentLocale = getCurrentLocale(); // 'ko', 'en', 'es-MX'
 * ```
 */
export function getCurrentLocale(): string {
    // 브라우저 언어 감지
    const browserLang = navigator.language || 'ko';

    // 지원하는 로케일 매핑
    const supportedLocales: Record<string, string> = {
        ko: 'ko',
        'ko-KR': 'ko',
        en: 'en',
        'en-US': 'en',
        es: 'es-MX',
        'es-MX': 'es-MX',
    };

    return supportedLocales[browserLang] || 'ko';
}

/**
 * 로케일별 번역 함수 생성
 *
 * @param locale - 고정할 로케일
 * @returns 해당 로케일의 번역 함수
 *
 * @example
 * ```tsx
 * const tKo = useLocale('ko');
 * const tEn = useLocale('en');
 *
 * tKo('customer.home.title') // '상점 목록'
 * tEn('customer.home.title') // 'Store List'
 * ```
 */
export function useLocale(locale: string): (key: string) => string {
    return (key: string) => trans(key, locale);
}
