<?php

return [
    // Page Title
    'title' => 'Registrierung',

    // Form Fields
    'first_name' => 'Vorname',
    'last_name' => 'Nachname',
    'email' => 'E-Mail-Adresse',
    'username' => 'Benutzername (optional)',
    'username_hint' => 'Leer lassen für automatische Generierung',
    'company_name' => 'Firmenname (optional)',
    'company_name_hint' => 'Leer lassen für persönlichen Workspace',
    'password' => 'Passwort',
    'password_confirmation' => 'Passwort bestätigen',
    'accept_terms' => 'Ich akzeptiere die',
    'terms_link' => 'Nutzungsbedingungen',

    // Buttons
    'register_button' => 'Registrieren',
    'already_have_account' => 'Bereits ein Konto?',
    'login_here' => 'Hier anmelden',

    // Validation Messages
    'validation' => [
        'email_unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
        'username_unique' => 'Dieser Benutzername ist bereits vergeben.',
        'username_regex' => 'Benutzername darf nur Buchstaben, Zahlen, Punkte, Bindestriche und Unterstriche enthalten.',
        'password_confirmed' => 'Passwort-Bestätigung stimmt nicht überein.',
        'accept_terms_required' => 'Sie müssen die Nutzungsbedingungen akzeptieren.',
    ],

    // API Responses
    'success' => 'Registrierung erfolgreich!',
    'failed' => 'Registrierung fehlgeschlagen',

    // Username Check
    'username_available' => 'Benutzername verfügbar',
    'username_taken' => 'Benutzername bereits vergeben',

    // Email Check
    'email_available' => 'E-Mail-Adresse verfügbar',
    'email_taken' => 'E-Mail-Adresse bereits registriert',

    // Role Descriptions (created during registration)
    'roles' => [
        'owner' => [
            'name' => 'Owner',
            'description' => 'Vollzugriff auf alles',
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Administrator mit fast allen Rechten',
        ],
        'user' => [
            'name' => 'User',
            'description' => 'Basis-Benutzer',
        ],
    ],
];
