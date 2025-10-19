<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/I18n/TranslationCompletenessTest.php

/**
 * Mensajes de traducción de Filament (Español - México)
 *
 * Define títulos y subtítulos del dashboard para cada panel.
 */
return [
    'platform' => [
        'dashboard' => [
            'title' => 'Panel de Plataforma',
            'subheading' => 'Ver métricas y estadísticas de todo el sistema',
        ],
    ],

    'system' => [
        'dashboard' => [
            'title' => 'Panel de Sistema',
            'subheading' => 'Ver configuración del sistema y estado operativo',
        ],
    ],

    'organization' => [
        'dashboard' => [
            'title' => 'Panel de Organización',
            'subheading' => 'Ver estado operativo y métricas clave de la organización',
        ],
    ],

    'brand' => [
        'dashboard' => [
            'title' => 'Panel de Marca',
            'subheading' => 'Ver ventas, pedidos y estadísticas de toda la marca',
        ],
    ],

    'store' => [
        'dashboard' => [
            'title' => 'Panel de Tienda',
            'subheading' => 'Ver pedidos en tiempo real, estadísticas de ventas e inventario',
        ],
    ],

    'organizations' => [
        'resource' => [
            'label' => 'Organización',
            'plural_label' => 'Organizaciones',
            'navigation_label' => 'Organizaciones',
        ],
        'fields' => [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'contact_email' => 'Correo Electrónico de Contacto',
            'contact_phone' => 'Teléfono de Contacto',
            'is_active' => 'Estado Activo',
        ],
        'columns' => [
            'name' => 'Nombre',
            'contact_email' => 'Correo Electrónico',
            'contact_phone' => 'Teléfono',
            'is_active' => 'Activo',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Fecha de Actualización',
        ],
        'actions' => [
            'activities' => 'Registro de Actividades',
            'back' => 'Volver a la Organización',
        ],
        'activities' => [
            'title' => 'Registro de Actividades: :name',
            'event_types' => [
                'created' => 'Creado',
                'updated' => 'Actualizado',
                'deleted' => 'Eliminado',
            ],
            'filters' => [
                'event_type' => 'Tipo de Evento',
            ],
            'columns' => [
                'event' => 'Evento',
                'user' => 'Usuario',
                'changes' => 'Cambios',
                'date' => 'Fecha',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'view' => 'Ver',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
        ],
    ],
];
