<?php

/**
 * English translations for tenant management
 * Mirrors German structure for consistency
 * Namespace: admin.tenants
 */
return [
    'page_title' => 'Tenant Management',
    'page_description' => 'Manage all system tenants',
    'counter_label' => ':count Tenants',
    'counter_text' => 'Tenants',
    'search_placeholder' => 'Search tenants...',
    'empty_text' => 'No tenants found',
    
    'table' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'users_count' => 'Users',
        'status' => 'Status',
    ],
    
    'types' => [
        'company' => 'Company',
        'personal' => 'Personal',
    ],
    
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'switch' => 'Switch to tenant',
        'delete' => 'Delete',
    ],

    'tooltips' => [
        'view' => 'View details',
        'edit' => 'Edit',
        'switch' => 'Switch to this tenant',
        'delete' => 'Delete',
    ],
    
    'detail' => [
        'fallback_title' => 'Tenant Details',
        'description' => 'Detailed view and management of tenant',
        'loading' => 'Loading tenant details...',
        
        'tabs' => [
            'overview' => 'Overview',
            'users' => 'Users',
            'settings' => 'Settings',
            'statistics' => 'Statistics',
        ],
        
        'basic_info' => 'Basic Information',
        'no_description' => 'No description',
        'actions_title' => 'Actions',
        'switch_button' => 'Switch to this tenant',
        'toggle_activate' => 'Activate',
        'toggle_deactivate' => 'Deactivate',
        
        'fields' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'type' => 'Type',
            'created_at' => 'Created at',
        ],
        
        'user_management' => 'User Management',
        'add_user' => 'Add user',
        'user_count_label' => 'Current: :current / :max users',
        
        'users_empty' => [
            'title' => 'User Management',
            'description' => 'A table with the users of this tenant would be displayed here.',
        ],
        
        'settings_title' => 'Tenant Settings',
        'form' => [
            'max_users' => 'Maximum number of users',
            'max_users_hint_current' => 'Current: :count',
            'max_users_hint_unlimited' => 'Unlimited',
            'tenant_type' => 'Tenant Type',
            'description' => 'Description',
            'is_active' => 'Tenant active',
            'reset_button' => 'Reset',
            'save_button' => 'Save',
        ],
        
        'tenant_types' => [
            'company' => 'Company',
            'personal' => 'Personal',
        ],
        
        'statistics' => [
            'active_users' => 'Active Users',
            'days_active' => 'Days Active',
            'activity' => 'Activity',
            'storage' => 'Storage',
            'activity_title' => 'Activity History',
        ],

        'activity_empty' => [
            'title' => 'Statistics in Development',
            'description' => 'Detailed activity statistics will be displayed here in the future.',
        ],
    ],
    
    'messages' => [
        'switch_success' => 'Switched to tenant ":name"',
        'switch_error' => 'Error switching tenant',
        'status_activated' => 'Tenant activated',
        'status_deactivated' => 'Tenant deactivated',
        'status_error' => 'Error changing status',
        'delete_confirm' => 'Do you really want to delete tenant ":name"? This action cannot be undone.',
        'delete_success' => 'Tenant deleted',
        'delete_error' => 'Error deleting tenant',
        'settings_saved' => 'Tenant settings saved',
        'settings_error' => 'Error saving settings',
        'create_success' => 'Tenant successfully created',
        'create_error' => 'Error creating tenant',
        'update_success' => 'Tenant successfully updated',
        'update_error' => 'Error saving',
        'json_error' => 'Invalid JSON format in settings',
        'not_found' => 'Tenant not found',
    ],

    'components' => [
        'create' => [
            'title' => 'Create New Tenant',
            'description' => 'Create a new tenant for your system. The slug will be automatically generated but can be customized.',
            'basic_info' => 'General Information',
            'settings' => 'Basic Settings',
            'preview' => 'Preview',
            'fields' => [
                'name' => 'Tenant Name',
                'name_hint' => 'The name of the tenant, e.g. \'My Company Ltd\'',
                'slug' => 'Slug (URL Identifier)',
                'slug_hint' => 'URL-friendly name, e.g. \'my-company\'',
                'slug_hint_edit' => 'Only lowercase letters, numbers and hyphens',
                'description' => 'Description (optional)',
                'description_hint' => 'Brief description of the tenant',
                'type' => 'Tenant Type',
                'type_hint' => 'Type of tenant - affects available features',
                'activate' => 'Activate immediately',
                'activate_hint' => 'The tenant can log in immediately after creation',
                'inactive_hint' => 'Inactive tenants cannot log in',
                'advanced_settings' => 'Advanced Settings',
                'settings_json' => 'Settings (JSON)',
                'settings_json_hint' => 'Advanced configuration as JSON object',
            ],
            'preview_labels' => [
                'name' => 'Name:',
                'slug' => 'Slug:',
                'type' => 'Type:',
                'status' => 'Status:',
                'no_name' => '(No name yet)',
                'auto_generated' => '(Will be auto-generated)',
            ],
            'buttons' => [
                'cancel' => 'Cancel',
                'create' => 'Create Tenant',
                'save' => 'Save',
            ],
        ],
        'view' => [
            'loading' => 'Loading tenant details...',
            'general_info' => 'General Information',
            'statistics' => 'Statistics',
            'users_title' => 'Users in this Tenant',
            'manage_users' => 'Manage Users',
            'users_loading' => 'Loading users...',
            'no_users_title' => 'No Users Assigned',
            'no_users_description' => 'No users have been assigned to this tenant yet.',
            'add_first_user' => 'Add First User',
            'show_all_users' => 'Show all :count users',
            'users_label' => 'Users',
            'users_count' => ':count users',
            'created_label' => 'Created at',
            'updated_label' => 'Last updated',
        ],
        'user_management' => [
            'title' => 'User Management: :name',
            'users_assigned' => ':count users assigned',
            'search_placeholder' => 'Search users...',
            'add_user_button' => 'Add User',
            'loading' => 'Loading users...',
            'no_users_assigned' => 'No Users Assigned',
            'no_users_found' => 'No Users Found',
            'no_users_assigned_description' => 'No users have been assigned to this tenant yet.',
            'no_users_found_description' => 'Try a different search term.',
            'add_first_user' => 'Add First User',
            'table' => [
                'name' => 'Name',
                'account' => 'Account',
                'role' => 'Role',
                'status' => 'Status',
                'added' => 'Added',
                'actions' => 'Actions',
            ],
            'status' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'roles' => [
                'global_admin' => 'Global Admin',
                'tenant_admin' => 'Tenant Admin',
                'tenant_member' => 'Tenant Member',
            ],
            'tooltips' => [
                'edit_role' => 'Edit Role',
                'remove' => 'Remove from Tenant',
            ],
            'assign_dialog' => [
                'title' => 'Add User to Tenant',
                'account_label' => 'Select Account',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email',
                'phone' => 'Phone (optional)',
                'role' => 'Role',
                'cancel' => 'Cancel',
                'add' => 'Add',
            ],
            'edit_dialog' => [
                'title' => 'Edit User Role',
                'new_role' => 'New Role',
                'cancel' => 'Cancel',
                'save' => 'Save',
            ],
            'messages' => [
                'load_users_error' => 'Error loading users',
                'load_accounts_error' => 'Error loading accounts',
                'assign_error' => 'Error adding user',
                'update_role_error' => 'Error updating role',
                'remove_error' => 'Error removing user',
                'remove_confirm' => 'Do you really want to remove :name from this tenant?',
            ],
        ],
    ],
];
