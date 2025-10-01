@extends('layouts.auth')

@section('title', __('auth.login_title'))

@section('content')
<div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, #FF6B35 0%, transparent 50%), radial-gradient(circle at 75% 75%, #FF4757 0%, transparent 50%);"></div>
    </div>

    <!-- Language Selector -->
    <x-language-selector />

    <!-- Theme Toggle -->
    <div class="absolute top-4 left-4 z-10">
        <label class="swap swap-rotate theme-toggle">
            <input type="checkbox" id="theme-toggle" onchange="toggleTheme()" />
            <svg class="swap-on fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/>
            </svg>
            <svg class="swap-off fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
            </svg>
        </label>
    </div>

    <!-- Main Login Container -->
    <div class="card w-full max-w-md auth-container bg-base-100 shadow-2xl relative z-20">
        <div class="card-body p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <!-- Olulo MX Logo -->
                    <div class="inline-flex items-center justify-center w-16 h-16 brand-gradient rounded-2xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl font-bold brand-text mb-2">{{ __('auth.app_name') }}</h1>
                <p class="text-base-content/70 text-sm">{{ __('auth.login_subtitle') }}</p>
            </div>

            <!-- Error Messages -->
            <div id="error-container" class="hidden mb-6">
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="error-message"></span>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loading-container" class="hidden mb-6">
                <div class="flex items-center justify-center space-x-2">
                    <span class="loading loading-spinner loading-md"></span>
                    <span>{{ __('auth.loading') }}</span>
                </div>
            </div>

            <!-- FirebaseUI Container -->
            <div id="firebaseui-auth-container" class="mb-6"></div>

            <!-- Alternative Login Methods Info -->
            <div class="text-center mt-6">
                <p class="text-xs text-base-content/60">
                    {{ __('auth.login_methods_info') }}
                </p>
            </div>

            <!-- Footer Links -->
            <div class="text-center mt-8 space-y-2">
                <div class="text-xs text-base-content/50">
                    <span>{{ __('auth.powered_by') }}</span>
                    <span class="brand-text font-semibold">{{ __('auth.app_name') }}</span>
                </div>
                <div class="text-xs text-base-content/40">
                    <a href="#" class="hover:text-primary">{{ __('auth.privacy_policy') }}</a>
                    <span class="mx-2">|</span>
                    <a href="#" class="hover:text-primary">{{ __('auth.terms_of_service') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Firebase config + emulator flag for Vite bundle -->
<script>
    window.firebaseConfig = {
        apiKey: "{{ config('firebase.web.api_key') }}",
        authDomain: "{{ config('firebase.web.auth_domain') }}",
        projectId: "{{ config('firebase.web.project_id') }}",
        storageBucket: "{{ config('firebase.web.storage_bucket') }}",
        messagingSenderId: "{{ config('firebase.web.messaging_sender_id') }}",
        appId: "{{ config('firebase.web.app_id') }}"
    };
    window.useAuthEmulator = {{ config('app.env') === 'local' ? 'true' : 'false' }};
    window.currentLanguage = @json(app()->getLocale());
    // Translated messages for JavaScript use
    window.authMessages = {
        loginError: @json(__('auth.login_error')),
        loginSuccess: @json(__('auth.login_success'))
    };
</script>

<!-- Page-specific Firebase/FirebaseUI bundle via Vite -->
@vite('resources/js/auth-login.js')

<script>
// Theme functions
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

// Firebase UI configuration
function getFirebaseUIConfig() {
    return {
        signInOptions: [
            // Email/Password
            {
                provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                requireDisplayName: true,
                signInMethod: firebase.auth.EmailAuthProvider.EMAIL_PASSWORD_SIGN_IN_METHOD
            },
            // Google
            {
                provider: firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                scopes: ['profile', 'email'],
                customParameters: {
                    prompt: 'select_account'
                }
            },
            // Phone
            {
                provider: firebase.auth.PhoneAuthProvider.PROVIDER_ID,
                recaptchaParameters: {
                    type: 'image',
                    size: 'compact',
                    badge: 'inline'
                },
                defaultCountry: 'MX',
                defaultNationalNumber: '',
                loginHint: '+52'
            }
        ],

        signInFlow: 'redirect',

        callbacks: {
            signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                showLoading(true);
                showMessage(window.authMessages.loginSuccess, 'success');

                authResult.user.getIdToken().then(function(idToken) {
                    const url = '{{ route("auth.firebase.callback") }}';
                    const payload = {
                        idToken: idToken,
                        user: {
                            uid: authResult.user.uid,
                            email: authResult.user.email,
                            displayName: authResult.user.displayName,
                            photoURL: authResult.user.photoURL,
                            phoneNumber: authResult.user.phoneNumber,
                            providerData: authResult.user.providerData
                        }
                    };

                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin'
                    })
                    .then(async response => {
                        const text = await response.text();
                        let data;
                        try { data = JSON.parse(text); } catch (_) { data = null; }
                        if (!response.ok || !data?.success) {
                            throw new Error((data && (data.message || JSON.stringify(data))) || text || 'Auth callback failed');
                        }
                        const redirectTo = data.redirect || '{{ route("dashboard") }}';
                        window.location.href = redirectTo;
                    })
                    .catch(error => {
                        showMessage(window.authMessages.loginError, 'error');
                        showLoading(false);
                    });
                }).catch(err => {
                    showMessage(window.authMessages.loginError, 'error');
                    showLoading(false);
                });

                return false;
            },

            uiShown: function() {
                showLoading(false);
            },

            signInFailure: function(error) {
                showMessage(window.authMessages.loginError, 'error');
                showLoading(false);
                return Promise.resolve();
            }
        },

        // Terms of service url/callback.
        tosUrl: '{{ url("/terms") }}',
        // Privacy policy url/callback.
        privacyPolicyUrl: '{{ url("/privacy") }}'
    };
}

// Initialize FirebaseUI
function initFirebaseUI() {
    if (!window.firebaseAuth) {
        console.error('Firebase auth not initialized');
        return;
    }

    // Delete existing instance
    if (window.firebaseUI) {
        window.firebaseUI.delete();
    }

    // Set language via Firebase Auth (used by FirebaseUI) before creating UI
    const languageMap = { 'ko': 'ko', 'en': 'en', 'es-MX': 'es' };
    window.firebaseAuth.languageCode = languageMap[window.currentLanguage] || 'en';

    // Create new instance
    window.firebaseUI = new firebaseui.auth.AuthUI(window.firebaseAuth);

    // Start UI
    window.firebaseUI.start('#firebaseui-auth-container', getFirebaseUIConfig());
}

// Utility functions
function showLoading(show) {
    const container = document.getElementById('loading-container');
    if (show) {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

function showMessage(message, type = 'error') {
    const container = document.getElementById('error-container');
    const messageElement = document.getElementById('error-message');

    messageElement.textContent = message;

    if (type === 'success') {
        container.querySelector('.alert').className = 'alert alert-success';
    } else {
        container.querySelector('.alert').className = 'alert alert-error';
    }

    container.classList.remove('hidden');

    // Auto hide after 5 seconds
    setTimeout(() => {
        container.classList.add('hidden');
    }, 5000);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.debug('[LoginPage] DOMContentLoaded', {
        url: window.location.href,
        currentLanguage: window.currentLanguage,
        useAuthEmulator: window.useAuthEmulator,
    });

    // Set initial theme toggle state
    const themeToggle = document.getElementById('theme-toggle');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    themeToggle.checked = currentTheme === 'light';

    // Show loading
    showLoading(true);

    // Initialize FirebaseUI when Firebase is ready
    const checkFirebase = setInterval(() => {
        if (window.firebaseAuth && window.firebaseui) {
            clearInterval(checkFirebase);
            try {
                initFirebaseUI();
            } catch (e) {
                showMessage('Firebase 초기화 실패. 페이지를 새로고침해주세요.', 'error');
                showLoading(false);
            }
        }
    }, 100);

    // Timeout after 10 seconds
    setTimeout(() => {
        clearInterval(checkFirebase);
        if (!window.firebaseAuth || !window.firebaseui) {
            showMessage('Firebase 초기화 실패. 페이지를 새로고침해주세요.', 'error');
            showLoading(false);
        }
    }, 10000);
});
</script>
@endpush