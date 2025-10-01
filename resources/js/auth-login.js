// resources/js/auth-login.js
// Initialize Firebase (compat) and expose global namespaces expected by existing code

import firebase from 'firebase/compat/app';
import 'firebase/compat/auth';
import * as firebaseui from 'firebaseui';
import 'firebaseui/dist/firebaseui.css';

// Read config injected by Blade into window
const cfg = window.firebaseConfig;
if (!cfg) {
    console.error('Missing window.firebaseConfig. Ensure the Blade view defines it before loading this script.');
}

// Initialize app if needed
if (!firebase.apps.length && cfg) {
    firebase.initializeApp(cfg);
}

// Expose globals for existing code compatibility
window.firebase = firebase;
window.firebaseui = firebaseui;

// Prepare Auth instance and expose it
const auth = firebase.auth();

// Connect to emulator when flag is set
if (window.useAuthEmulator) {
    try {
        auth.useEmulator('http://localhost:9099');
    } catch (e) {
        console.warn('Auth emulator connection failed or already set:', e);
    }
}

// Make available to page scripts
window.firebaseAuth = auth;
