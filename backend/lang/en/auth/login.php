<?php

/**
 * English translations for login
 * Contains all login-related translations (frontend UI + backend messages)
 * Namespace: auth.login
 */
return [
    // Page/View Title
    'page_title' => 'Login',
    'page_description' => 'Sign in to your account',

    // Form Fields (Frontend UI Labels)
    'fields' => [
        'identifier' => [
            'label' => 'Username or Email',
            'placeholder' => 'Enter your username or email',
            'hint' => 'You can login with your username or email address',
        ],
        'password' => [
            'label' => 'Password',
            'placeholder' => 'Enter your password',
        ],
        'remember_me' => [
            'label' => 'Remember me',
        ],
    ],

    // Buttons (Frontend UI)
    'buttons' => [
        'submit' => 'Login',
        'submitting' => 'Logging in...',
    ],

    // Links (Frontend UI)
    'links' => [
        'forgot_password' => 'Forgot password?',
        'no_account' => 'Don\'t have an account?',
        'register' => 'Register here',
        'back_to_home' => 'Back to home',
    ],

    // Backend Response Messages
    'messages' => [
        'success' => 'Successfully logged in',
        'failed' => 'Invalid credentials or account inactive',
        'invalid_credentials' => 'Invalid credentials',
        'account_inactive' => 'Your account is inactive',
        'account_not_verified' => 'Please verify your email address first',
        'unauthenticated' => 'Unauthenticated',
        'session_expired' => 'Your session has expired. Please login again',
        'logout_success' => 'Successfully logged out',
    ],

    // Validation Messages (specific to login)
    'validation' => [
        'identifier_required' => 'Username or email is required',
        'password_required' => 'Password is required',
        'credentials_invalid' => 'The provided credentials are invalid',
    ],

    // Error Messages (Frontend Display)
    'errors' => [
        'general' => 'Login failed',
        'network' => 'Network error. Please check your internet connection',
        'server' => 'Server error. Please try again later',
        'too_many_attempts' => 'Too many login attempts. Please wait :seconds seconds',
    ],

    // Loading States (Frontend UI)
    'loading' => [
        'authenticating' => 'Authenticating...',
        'redirecting' => 'Redirecting...',
    ],

    // Quick Login (Development Mode)
    'quick_login' => [
        'title' => 'Quick Login (Development)',
        'description' => 'Quick login for development',
        'select_account' => 'Select account',
        'dev_only' => 'Quick login only available in development',
        'success' => 'Quick login successful',
        'failed' => 'Quick login failed',
    ],
];
