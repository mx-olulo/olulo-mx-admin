<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/I18n/TranslationCompletenessTest.php

/**
 * Filament Translation Messages (English)
 *
 * Defines dashboard titles and subheadings for each panel.
 */
return [
    'platform' => [
        'dashboard' => [
            'title' => 'Platform Dashboard',
            'subheading' => 'View system-wide metrics and statistics',
        ],
    ],

    'system' => [
        'dashboard' => [
            'title' => 'System Dashboard',
            'subheading' => 'View system settings and operational status',
        ],
    ],

    'organization' => [
        'dashboard' => [
            'title' => 'Organization Dashboard',
            'subheading' => 'View organization-wide operational status and key metrics',
        ],
    ],

    'brand' => [
        'dashboard' => [
            'title' => 'Brand Dashboard',
            'subheading' => 'View brand-wide sales, orders, and statistics',
        ],
    ],

    'store' => [
        'dashboard' => [
            'title' => 'Store Dashboard',
            'subheading' => 'View real-time orders, sales statistics, and inventory status',
        ],
    ],

    'organizations' => [
        'resource' => [
            'label' => 'Organization',
            'plural_label' => 'Organizations',
            'navigation_label' => 'Organizations',
        ],
        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'contact_email' => 'Contact Email',
            'contact_phone' => 'Contact Phone',
            'is_active' => 'Active Status',
        ],
        'columns' => [
            'name' => 'Name',
            'contact_email' => 'Email',
            'contact_phone' => 'Phone',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'actions' => [
            'activities' => 'Activity Log',
            'back' => 'Back to Organization',
        ],
        'activities' => [
            'title' => 'Activity Log: :name',
            'event_types' => [
                'created' => 'Created',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
            ],
            'filters' => [
                'event_type' => 'Event Type',
            ],
            'columns' => [
                'event' => 'Event',
                'user' => 'User',
                'changes' => 'Changes',
                'date' => 'Date',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],
    ],
];
