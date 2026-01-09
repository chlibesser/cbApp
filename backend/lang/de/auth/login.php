<?php

/**
 * German translations for login
 * Contains all login-related translations (frontend UI + backend messages)
 * Namespace: auth.login
 */
return [
    // Page/View Title
    'page_title' => 'Anmelden',
    'page_description' => 'Melden Sie sich bei Ihrem Konto an',

    // Form Fields (Frontend UI Labels)
    'fields' => [
        'identifier' => [
            'label' => 'Benutzername oder E-Mail',
            'placeholder' => 'Geben Sie Ihren Benutzername oder E-Mail ein',
            'hint' => 'Sie können sich mit Benutzername oder E-Mail-Adresse anmelden',
        ],
        'password' => [
            'label' => 'Passwort',
            'placeholder' => 'Geben Sie Ihr Passwort ein',
        ],
        'remember_me' => [
            'label' => 'Angemeldet bleiben',
        ],
    ],

    // Buttons (Frontend UI)
    'buttons' => [
        'submit' => 'Anmelden',
        'submitting' => 'Anmeldung läuft...',
    ],

    // Links (Frontend UI)
    'links' => [
        'forgot_password' => 'Passwort vergessen?',
        'no_account' => 'Noch kein Konto?',
        'register' => 'Hier registrieren',
        'back_to_home' => 'Zurück zur Startseite',
    ],

    // Backend Response Messages
    'messages' => [
        'success' => 'Erfolgreich angemeldet',
        'failed' => 'Ungültige Anmeldedaten oder Account inaktiv',
        'invalid_credentials' => 'Ungültige Anmeldedaten',
        'account_inactive' => 'Ihr Account ist inaktiv',
        'account_not_verified' => 'Bitte verifizieren Sie zuerst Ihre E-Mail-Adresse',
        'unauthenticated' => 'Nicht authentifiziert',
        'session_expired' => 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an',
        'logout_success' => 'Erfolgreich abgemeldet',
    ],

    // Validation Messages (specific to login)
    'validation' => [
        'identifier_required' => 'Benutzername oder E-Mail ist erforderlich',
        'password_required' => 'Passwort ist erforderlich',
        'credentials_invalid' => 'Die eingegebenen Anmeldedaten sind ungültig',
    ],

    // Error Messages (Frontend Display)
    'errors' => [
        'general' => 'Anmeldung fehlgeschlagen',
        'network' => 'Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung',
        'server' => 'Serverfehler. Bitte versuchen Sie es später erneut',
        'too_many_attempts' => 'Zu viele Anmeldeversuche. Bitte warten Sie :seconds Sekunden',
    ],

    // Loading States (Frontend UI)
    'loading' => [
        'authenticating' => 'Authentifizierung läuft...',
        'redirecting' => 'Weiterleitung...',
    ],

    // Quick Login (Development Mode)
    'quick_login' => [
        'title' => 'Quick Login (Entwicklung)',
        'description' => 'Schneller Login für Entwicklung',
        'select_account' => 'Account auswählen',
        'dev_only' => 'Quick Login nur in Entwicklungsumgebung verfügbar',
        'success' => 'Quick Login erfolgreich',
        'failed' => 'Quick Login fehlgeschlagen',
    ],
];
