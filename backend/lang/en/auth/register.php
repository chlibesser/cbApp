<?php

return [
    // Page Title
    'title' => 'Registration',

    // Form Fields
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'email' => 'Email Address',
    'username' => 'Username (optional)',
    'username_hint' => 'Leave empty for automatic generation',
    'company_name' => 'Company Name (optional)',
    'company_name_hint' => 'Leave empty for personal workspace',
    'password' => 'Password',
    'password_confirmation' => 'Confirm Password',
    'accept_terms' => 'I accept the',
    'terms_link' => 'Terms of Service',

    // Buttons
    'register_button' => 'Register',
    'already_have_account' => 'Already have an account?',
    'login_here' => 'Login here',

    // Validation Messages
    'validation' => [
        'email_unique' => 'This email address is already registered.',
        'username_unique' => 'This username is already taken.',
        'username_regex' => 'Username may only contain letters, numbers, dots, hyphens, and underscores.',
        'password_confirmed' => 'Password confirmation does not match.',
        'accept_terms_required' => 'You must accept the Terms of Service.',
    ],

    // API Responses
    'success' => 'Registration successful!',
    'failed' => 'Registration failed',

    // Username Check
    'username_available' => 'Username available',
    'username_taken' => 'Username already taken',

    // Email Check
    'email_available' => 'Email address available',
    'email_taken' => 'Email address already registered',

    // Role Descriptions (created during registration)
    'roles' => [
        'owner' => [
            'name' => 'Owner',
            'description' => 'Full access to everything',
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Administrator with almost all rights',
        ],
        'user' => [
            'name' => 'User',
            'description' => 'Basic user',
        ],
    ],
];
