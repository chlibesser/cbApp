<?php

/**
 * German translations for account management
 * Used in admin interface for user account operations
 * Namespace: admin.accounts
 */
return [
    'page_title' => 'Account-Verwaltung',
    'page_description' => 'Verwalten Sie alle System-Accounts',
    'counter_label' => ':count Accounts',
    'counter_text' => 'Accounts',
    'search_placeholder' => 'Accounts durchsuchen...',
    'empty_text' => 'Keine Accounts gefunden',
    
    'table' => [
        'username' => 'Benutzername',
        'email' => 'E-Mail',
        'role' => 'Rolle',
        'verified' => 'Verifiziert',
        'last_login' => 'Letzter Login',
    ],
    
    'roles' => [
        'global_admin' => 'Global Admin',
        'tenant_admin' => 'Tenant Admin',
        'member' => 'Mitglied',
    ],
    
    'verification_status' => [
        'verified' => 'Verifiziert',
        'not_verified' => 'Nicht verifiziert',
    ],
    
    'actions' => [
        'view' => 'Ansehen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
    ],

    'tooltips' => [
        'view' => 'Details ansehen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
    ],
    
    'messages' => [
        'delete_confirm' => 'Möchten Sie den Account ":email" wirklich löschen?',
        'delete_success' => 'Account wurde gelöscht',
        'delete_error' => 'Fehler beim Löschen des Accounts',
        'create_success' => 'Account wurde erfolgreich erstellt',
        'create_error' => 'Fehler beim Erstellen des Accounts',
        'update_success' => 'Account wurde erfolgreich aktualisiert',
        'update_error' => 'Fehler beim Speichern des Accounts',
        'not_found' => 'Account nicht gefunden',
    ],

    'components' => [
        'view' => [
            'loading' => 'Account-Details werden geladen...',
            'general_info' => 'Allgemeine Informationen',
            'timestamps' => 'Zeitstempel',
            'assigned_tenants' => 'Zugewiesene Tenants',
            'no_tenants_title' => 'Keine Tenants zugewiesen',
            'no_tenants_description' => 'Dieser Account ist noch keinem Tenant zugewiesen.',
            'created_label' => 'Erstellt am',
            'updated_label' => 'Zuletzt aktualisiert',
            'email_verified_label' => 'E-Mail verifiziert',
        ],
        'create' => [
            'title' => 'Neuen Account erstellen',
            'description' => 'Erstellen Sie einen neuen Account im System. Der Account erhält standardmäßig die Mitglieder-Rolle.',
            'basic_info' => 'Grundinformationen',
            'settings' => 'Grundeinstellungen',
            'preview' => 'Vorschau',
            'fields' => [
                'username' => 'Benutzername',
                'username_hint' => 'Eindeutiger Benutzername für die Anmeldung',
                'email' => 'E-Mail',
                'email_hint' => 'E-Mail-Adresse für Benachrichtigungen',
                'password' => 'Passwort',
                'password_hint' => 'Mindestens 8 Zeichen',
                'password_confirmation' => 'Passwort bestätigen',
                'password_confirmation_hint' => 'Passwort erneut eingeben',
                'system_role' => 'System-Rolle',
                'system_role_hint' => 'Globale Berechtigungen im System',
                'activate' => 'Account sofort aktivieren',
                'activate_hint' => 'Der Account kann sich sofort nach der Erstellung anmelden',
            ],
            'preview_labels' => [
                'username' => 'Benutzername:',
                'email' => 'E-Mail:',
                'system_role' => 'System-Rolle:',
                'status' => 'Status:',
                'no_username' => '(Noch kein Benutzername)',
                'no_email' => '(Noch keine E-Mail)',
            ],
            'buttons' => [
                'cancel' => 'Abbrechen',
                'create' => 'Account erstellen',
                'save' => 'Speichern',
            ],
        ],
        'edit' => [
            'title' => 'Account bearbeiten',
            'general_info' => 'Allgemeine Informationen',
            'settings' => 'Einstellungen',
            'password_change' => 'Passwort ändern',
            'fields' => [
                'username' => 'Benutzername',
                'email' => 'E-Mail',
                'system_role' => 'System-Rolle',
                'is_active' => 'Account aktiv',
                'is_active_hint' => 'Inaktive Accounts können sich nicht anmelden',
                'new_password' => 'Neues Passwort',
                'password_confirmation' => 'Passwort bestätigen',
            ],
            'buttons' => [
                'cancel' => 'Abbrechen',
                'save' => 'Speichern',
            ],
        ],
    ],

    'fields' => [
        'username' => 'Benutzername',
        'email' => 'E-Mail',
        'system_role' => 'System-Rolle',
        'status' => 'Status',
    ],

    'status' => [
        'active' => 'Aktiv',
        'locked' => 'Gesperrt',
    ],

    'system_roles' => [
        'admin' => 'Administrator',
        'tenant_admin' => 'Tenant Administrator',
        'member' => 'Mitglied',
        'no_role' => 'Keine Rolle',
    ],
];
