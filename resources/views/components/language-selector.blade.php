@props(['position' => 'top-4 right-4'])

@php
    $languageNames = [
        'ko' => '한국어',
        'en' => 'English',
        'es-MX' => 'Español'
    ];
    $languageFlags = [
        'ko' => '🇰🇷',
        'en' => '🇺🇸',
        'es-MX' => '🇲🇽'
    ];
    $currentLocale = app()->getLocale();
@endphp

<div class="absolute {{ $position }} z-10">
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-sm language-selector rounded-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
            <span class="ml-1">{{ $languageNames[$currentLocale] ?? '한국어' }}</span>
        </div>
        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-40">
            @foreach($languageNames as $locale => $name)
                <li>
                    <a class="text-sm {{ $locale === $currentLocale ? 'active' : '' }}"
                       href="{{ url()->current() . '?' . http_build_query(array_merge(request()->query(), ['locale' => $locale])) }}">
                        {{ $languageFlags[$locale] }} {{ $name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>