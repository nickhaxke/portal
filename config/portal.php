<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Executive Portal Registration & Access Governance
    |--------------------------------------------------------------------------
    |
    | Configure reader account registration policies pending product owner
    | executive approval. Allows seamless switching between open registration,
    | domain-restricted institutional access, or invite-only mode.
    |
    */

    'registration_enabled' => env('PORTAL_REGISTRATION_ENABLED', true),

    // Options: 'open', 'institutional_domain', 'approval_required'
    'registration_policy' => env('PORTAL_REGISTRATION_POLICY', 'open'),

    // Allowed institutional email domains (comma-separated or array)
    'allowed_domains' => array_filter(explode(',', env('PORTAL_ALLOWED_DOMAINS', ''))),

    // Enforce email verification before reader access
    'require_email_verification' => env('PORTAL_REQUIRE_EMAIL_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Publication Types Specification
    |--------------------------------------------------------------------------
    */
    'publication_types' => [
        'book' => [
            'name' => 'Book / Monograph',
            'badge' => 'Book',
            'icon' => 'book',
        ],
        'research_paper' => [
            'name' => 'Research Paper',
            'badge' => 'Research Paper',
            'icon' => 'microscope',
        ],
        'report' => [
            'name' => 'Executive Report',
            'badge' => 'Executive Report',
            'icon' => 'chart',
        ],
        'policy_document' => [
            'name' => 'Policy Document',
            'badge' => 'Policy Document',
            'icon' => 'shield',
        ],
        'thesis' => [
            'name' => 'Thesis / Dissertation',
            'badge' => 'Dissertation',
            'icon' => 'academic',
        ],
    ],
];
