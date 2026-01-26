<?php

/**
 * English common translations for admin interface
 * Mirrors German structure exactly
 * Namespace: admin.common
 */
return [
    // Common Buttons
    'buttons' => [
        'create' => 'Create',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
        'back' => 'Back',
        'reset' => 'Reset',
        'search' => 'Search',
        'filter' => 'Filter',
        'export' => 'Export',
        'import' => 'Import',
        'refresh' => 'Refresh',
        'close' => 'Close',
        'create_first' => 'Create first entry',
    ],
    
    // Common Field Labels
    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'description' => 'Description',
        'type' => 'Type',
        'status' => 'Status',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'tenants' => 'Tenants',
        'users' => 'Users',
    ],
    
    // Common Status Labels
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ],
    
    // Filter Options
    'filter' => [
        'all' => 'All',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'clear' => 'Clear filters',
        'apply' => 'Apply',
        'create' => 'Create filter',
        'update' => 'Update filter',
        'reset_all' => 'Reset all',
        'dialog' => [
            'save_title' => 'Save filter',
            'edit_title' => 'Edit filter',
            'name_label' => 'Filter name',
            'color_label' => 'Button color',
            'show_as_button' => 'Show as quick-access button',
            'validation' => [
                'required' => 'This field is required',
                'max_length' => 'Maximum 100 characters allowed',
            ],
        ],
    ],
    
    // Pagination
    'pagination' => [
        'showing' => 'Showing :from to :to of :total entries',
        'per_page' => 'Per page',
        'first' => 'First',
        'last' => 'Last',
        'previous' => 'Previous',
        'next' => 'Next',
    ],
    
    // Common Messages
    'messages' => [
        'loading' => 'Loading...',
        'no_data' => 'No data available',
        'no_entries' => 'No entries have been created yet.',
        'load_error' => 'Error loading data',
        'error' => 'An error occurred',
        'success' => 'Success',
    ],
];
