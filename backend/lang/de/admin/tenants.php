<?php

/**
 * German translations for tenant management
 * Used in admin interface for tenant CRUD operations
 * Namespace: admin.tenants
 */
return [
    'page_title' => 'Tenant-Verwaltung',
    'page_description' => 'Verwalten Sie alle System-Tenants',
    'counter_label' => ':count Tenants',
    'counter_text' => 'Tenants',
    'search_placeholder' => 'Tenants durchsuchen...',
    'empty_text' => 'Keine Tenants gefunden',
    
    'table' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'users_count' => 'Benutzer',
        'status' => 'Status',
    ],
    
    'types' => [
        'company' => 'Unternehmen',
        'personal' => 'Persönlich',
    ],
    
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
    ],
    
    'actions' => [
        'view' => 'Ansehen',
        'edit' => 'Bearbeiten',
        'switch' => 'Zu Tenant wechseln',
        'delete' => 'Löschen',
    ],

    'tooltips' => [
        'view' => 'Details ansehen',
        'edit' => 'Bearbeiten',
        'switch' => 'Zu diesem Tenant wechseln',
        'delete' => 'Löschen',
    ],
    
    'detail' => [
        'fallback_title' => 'Tenant Details',
        'description' => 'Detaillierte Ansicht und Verwaltung des Tenants',
        'loading' => 'Tenant-Details werden geladen...',
        
        'tabs' => [
            'overview' => 'Übersicht',
            'users' => 'Benutzer',
            'settings' => 'Einstellungen',
            'statistics' => 'Statistiken',
        ],
        
        'basic_info' => 'Basis-Informationen',
        'no_description' => 'Keine Beschreibung',
        'actions_title' => 'Aktionen',
        'switch_button' => 'Zu diesem Tenant wechseln',
        'toggle_activate' => 'Aktivieren',
        'toggle_deactivate' => 'Deaktivieren',
        
        'fields' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Beschreibung',
            'type' => 'Typ',
            'created_at' => 'Erstellt am',
        ],
        
        'user_management' => 'Benutzer-Verwaltung',
        'add_user' => 'Benutzer hinzufügen',
        'user_count_label' => 'Aktuell: :current / :max Benutzer',
        
        'users_empty' => [
            'title' => 'Benutzer-Verwaltung',
            'description' => 'Hier würde eine Tabelle mit den Benutzern dieses Tenants angezeigt.',
        ],
        
        'settings_title' => 'Tenant-Einstellungen',
        'form' => [
            'max_users' => 'Maximale Anzahl Benutzer',
            'max_users_hint_current' => 'Aktuell: :count',
            'max_users_hint_unlimited' => 'Unbegrenzt',
            'tenant_type' => 'Tenant-Typ',
            'description' => 'Beschreibung',
            'is_active' => 'Tenant aktiv',
            'reset_button' => 'Zurücksetzen',
            'save_button' => 'Speichern',
        ],
        
        'tenant_types' => [
            'company' => 'Unternehmen',
            'personal' => 'Persönlich',
        ],
        
        'statistics' => [
            'active_users' => 'Aktive Benutzer',
            'days_active' => 'Tage aktiv',
            'activity' => 'Aktivität',
            'storage' => 'Speicher',
            'activity_title' => 'Aktivitäts-Verlauf',
        ],

        'activity_empty' => [
            'title' => 'Statistiken in Entwicklung',
            'description' => 'Hier werden zukünftig detaillierte Aktivitäts-Statistiken angezeigt.',
        ],
    ],
    
    'messages' => [
        'switch_success' => 'Zu Tenant ":name" gewechselt',
        'switch_error' => 'Fehler beim Wechseln des Tenants',
        'status_activated' => 'Tenant aktiviert',
        'status_deactivated' => 'Tenant deaktiviert',
        'status_error' => 'Fehler beim Ändern des Status',
        'delete_confirm' => 'Möchten Sie den Tenant ":name" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.',
        'delete_success' => 'Tenant wurde gelöscht',
        'delete_error' => 'Fehler beim Löschen des Tenants',
        'settings_saved' => 'Tenant-Einstellungen wurden gespeichert',
        'settings_error' => 'Fehler beim Speichern der Einstellungen',
        'create_success' => 'Tenant wurde erfolgreich erstellt',
        'create_error' => 'Fehler beim Erstellen des Tenants',
        'update_success' => 'Tenant wurde erfolgreich aktualisiert',
        'update_error' => 'Fehler beim Speichern',
        'json_error' => 'Ungültiges JSON-Format in den Einstellungen',
        'not_found' => 'Tenant nicht gefunden',
    ],

    'components' => [
        'create' => [
            'title' => 'Neuen Tenant erstellen',
            'description' => 'Erstellen Sie einen neuen Tenant für Ihr System. Der Slug wird automatisch generiert, kann aber angepasst werden.',
            'basic_info' => 'Allgemeine Informationen',
            'settings' => 'Grundeinstellungen',
            'preview' => 'Vorschau',
            'fields' => [
                'name' => 'Tenant-Name',
                'name_hint' => 'Der Name des Tenants, z.B. \'Meine Firma GmbH\'',
                'slug' => 'Slug (URL-Bezeichnung)',
                'slug_hint' => 'URL-freundlicher Name, z.B. \'meine-firma\'',
                'slug_hint_edit' => 'Nur Kleinbuchstaben, Zahlen und Bindestriche',
                'description' => 'Beschreibung (optional)',
                'description_hint' => 'Kurze Beschreibung des Tenants',
                'type' => 'Tenant-Typ',
                'type_hint' => 'Art des Tenants - beeinflusst verfügbare Features',
                'activate' => 'Sofort aktivieren',
                'activate_hint' => 'Der Tenant kann sich sofort nach der Erstellung anmelden',
                'inactive_hint' => 'Inaktive Tenants können sich nicht anmelden',
                'advanced_settings' => 'Erweiterte Einstellungen',
                'settings_json' => 'Einstellungen (JSON)',
                'settings_json_hint' => 'Erweiterte Konfiguration als JSON-Objekt',
            ],
            'preview_labels' => [
                'name' => 'Name:',
                'slug' => 'Slug:',
                'type' => 'Typ:',
                'status' => 'Status:',
                'no_name' => '(Noch kein Name)',
                'auto_generated' => '(Wird automatisch generiert)',
            ],
            'buttons' => [
                'cancel' => 'Abbrechen',
                'create' => 'Tenant erstellen',
                'save' => 'Speichern',
            ],
        ],
        'view' => [
            'loading' => 'Tenant-Details werden geladen...',
            'general_info' => 'Allgemeine Informationen',
            'statistics' => 'Statistiken',
            'users_title' => 'Benutzer in diesem Tenant',
            'manage_users' => 'Benutzer verwalten',
            'users_loading' => 'Benutzer werden geladen...',
            'no_users_title' => 'Keine Benutzer zugewiesen',
            'no_users_description' => 'Diesem Tenant sind noch keine Benutzer zugewiesen.',
            'add_first_user' => 'Ersten Benutzer hinzufügen',
            'show_all_users' => 'Alle :count Benutzer anzeigen',
            'users_label' => 'Benutzer',
            'users_count' => ':count Benutzer',
            'created_label' => 'Erstellt am',
            'updated_label' => 'Zuletzt aktualisiert',
        ],
        'user_management' => [
            'title' => 'Benutzer-Verwaltung: :name',
            'users_assigned' => ':count Benutzer zugewiesen',
            'search_placeholder' => 'Benutzer durchsuchen...',
            'add_user_button' => 'Benutzer hinzufügen',
            'loading' => 'Benutzer werden geladen...',
            'no_users_assigned' => 'Keine Benutzer zugewiesen',
            'no_users_found' => 'Keine Benutzer gefunden',
            'no_users_assigned_description' => 'Diesem Tenant sind noch keine Benutzer zugewiesen.',
            'no_users_found_description' => 'Versuchen Sie einen anderen Suchbegriff.',
            'add_first_user' => 'Ersten Benutzer hinzufügen',
            'table' => [
                'name' => 'Name',
                'account' => 'Account',
                'role' => 'Rolle',
                'status' => 'Status',
                'added' => 'Hinzugefügt',
                'actions' => 'Aktionen',
            ],
            'status' => [
                'active' => 'Aktiv',
                'inactive' => 'Inaktiv',
            ],
            'roles' => [
                'global_admin' => 'Global Admin',
                'tenant_admin' => 'Tenant Admin',
                'tenant_member' => 'Tenant Member',
            ],
            'tooltips' => [
                'edit_role' => 'Rolle bearbeiten',
                'remove' => 'Aus Tenant entfernen',
            ],
            'assign_dialog' => [
                'title' => 'Benutzer zum Tenant hinzufügen',
                'account_label' => 'Account auswählen',
                'first_name' => 'Vorname',
                'last_name' => 'Nachname',
                'email' => 'E-Mail',
                'phone' => 'Telefon (optional)',
                'role' => 'Rolle',
                'cancel' => 'Abbrechen',
                'add' => 'Hinzufügen',
            ],
            'edit_dialog' => [
                'title' => 'Benutzerrolle bearbeiten',
                'new_role' => 'Neue Rolle',
                'cancel' => 'Abbrechen',
                'save' => 'Speichern',
            ],
            'messages' => [
                'load_users_error' => 'Fehler beim Laden der Benutzer',
                'load_accounts_error' => 'Fehler beim Laden der Accounts',
                'assign_error' => 'Fehler beim Hinzufügen des Benutzers',
                'update_role_error' => 'Fehler beim Aktualisieren der Rolle',
                'remove_error' => 'Fehler beim Entfernen des Benutzers',
                'remove_confirm' => 'Möchten Sie :name wirklich aus diesem Tenant entfernen?',
            ],
        ],
    ],
];
