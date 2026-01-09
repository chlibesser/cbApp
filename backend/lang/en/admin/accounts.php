<?php

/**
 * English translations for account management  
 * Mirrors German structure
 * Namespace: admin.accounts
 */
return [
    'page_title' => 'Account Management',
    'page_description' => 'Manage all system accounts',
    'counter_label' => ':count Accounts',
    'counter_text' => 'Accounts',
    'search_placeholder' => 'Search accounts...',
    'empty_text' => 'No accounts found',
    
    'table' => [
        'username' => 'Username',
        'email' => 'Email',
        'role' => 'Role',
        'verified' => 'Verified',
        'last_login' => 'Last Login',
    ],
    
    'roles' => [
        'global_admin' => 'Global Admin',
        'tenant_admin' => 'Tenant Admin',
        'member' => 'Member',
    ],
    
    'verification_status' => [
        'verified' => 'Verified',
        'not_verified' => 'Not verified',
    ],
    
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'tooltips' => [
        'view' => 'View details',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
    
    'messages' => [
        'delete_confirm' => 'Do you really want to delete account ":email"?',
        'delete_success' => 'Account deleted',
        'delete_error' => 'Error deleting account',
        'create_success' => 'Account successfully created',
        'create_error' => 'Error creating account',
        'update_success' => 'Account successfully updated',
        'update_error' => 'Error saving account',
        'not_found' => 'Account not found',
    ],

    'components' => [
        'view' => [
            'loading' => 'Loading account details...',
            'general_info' => 'General Information',
            'timestamps' => 'Timestamps',
            'assigned_tenants' => 'Assigned Tenants',
            'no_tenants_title' => 'No Tenants Assigned',
            'no_tenants_description' => 'This account is not assigned to any tenant yet.',
            'created_label' => 'Created at',
            'updated_label' => 'Last updated',
            'email_verified_label' => 'Email verified',
        ],
        'create' => [
            'title' => 'Create New Account',
            'description' => 'Create a new account in the system. The account receives the member role by default.',
            'basic_info' => 'Basic Information',
            'settings' => 'Basic Settings',
            'preview' => 'Preview',
            'fields' => [
                'username' => 'Username',
                'username_hint' => 'Unique username for login',
                'email' => 'Email',
                'email_hint' => 'Email address for notifications',
                'password' => 'Password',
                'password_hint' => 'At least 8 characters',
                'password_confirmation' => 'Confirm Password',
                'password_confirmation_hint' => 'Re-enter password',
                'system_role' => 'System Role',
                'system_role_hint' => 'Global permissions in the system',
                'activate' => 'Activate account immediately',
                'activate_hint' => 'The account can log in immediately after creation',
            ],
            'preview_labels' => [
                'username' => 'Username:',
                'email' => 'Email:',
                'system_role' => 'System Role:',
                'status' => 'Status:',
                'no_username' => '(No username yet)',
                'no_email' => '(No email yet)',
            ],
            'buttons' => [
                'cancel' => 'Cancel',
                'create' => 'Create Account',
                'save' => 'Save',
            ],
        ],
        'edit' => [
            'title' => 'Edit Account',
            'general_info' => 'General Information',
            'settings' => 'Settings',
            'password_change' => 'Change Password',
            'fields' => [
                'username' => 'Username',
                'email' => 'Email',
                'system_role' => 'System Role',
                'is_active' => 'Account active',
                'is_active_hint' => 'Inactive accounts cannot log in',
                'new_password' => 'New Password',
                'password_confirmation' => 'Confirm Password',
            ],
            'buttons' => [
                'cancel' => 'Cancel',
                'save' => 'Save',
            ],
        ],
    ],

    'fields' => [
        'username' => 'Username',
        'email' => 'Email',
        'system_role' => 'System Role',
        'status' => 'Status',
    ],

    'status' => [
        'active' => 'Active',
        'locked' => 'Locked',
    ],

    'system_roles' => [
        'admin' => 'Administrator',
        'tenant_admin' => 'Tenant Administrator',
        'member' => 'Member',
        'no_role' => 'No Role',
    ],
];
