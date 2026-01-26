<?php

return [
    'buttons' => [
        'create' => 'Erstellen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'delete' => 'Löschen',
        'edit' => 'Bearbeiten',
        'view' => 'Ansehen',
        'back' => 'Zurück',
        'reset' => 'Zurücksetzen',
        'search' => 'Suchen...',
        'filter' => 'Filtern',
        'export' => 'Exportieren',
        'import' => 'Importieren',
        'refresh' => 'Aktualisieren',
        'close' => 'Schließen',
        'create_first' => 'Ersten Eintrag erstellen',
    ],
    
    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'description' => 'Beschreibung',
        'type' => 'Typ',
        'status' => 'Status',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
        'tenants' => 'Tenants',
    ],
    
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
    ],
    
    'filter' => [
        'all' => 'Alle',
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'clear' => 'Filter zurücksetzen',
        'create' => 'Filter erstellen',
        'update' => 'Filter aktualisieren',
        'reset_all' => 'Alles zurücksetzen',
        'dialog' => [
            'save_title' => 'Filter speichern',
            'edit_title' => 'Filter bearbeiten',
            'name_label' => 'Filter-Name',
            'color_label' => 'Button-Farbe',
            'show_as_button' => 'Als Quick-Access Button anzeigen',
            'validation' => [
                'required' => 'Dieses Feld ist erforderlich',
                'max_length' => 'Maximal 100 Zeichen erlaubt',
            ],
        ],
    ],
    
    'pagination' => [
        'showing' => 'Zeige :from bis :to von :total Einträgen',
        'per_page' => 'Pro Seite',
    ],

    'messages' => [
        'loading' => 'Lädt...',
        'no_data' => 'Keine Daten vorhanden',
        'no_entries' => 'Es wurden noch keine Einträge erstellt.',
        'load_error' => 'Fehler beim Laden der Daten',
        'error' => 'Ein Fehler ist aufgetreten',
        'success' => 'Erfolgreich',
    ],
];
