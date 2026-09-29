<?php

/*
|--------------------------------------------------------------------------
| Inertia
|--------------------------------------------------------------------------
|
| The package's own configuration, with one thing changed: the page components
| live in "js/pages" rather than in "js/Pages". The two are the same folder on
| Windows and different ones on Linux, so the spelling is written down here
| instead of being left to the filesystem to be forgiving about — otherwise the
| tests that assert a component pass locally and fail everywhere else.
|
| The file is kept whole rather than as a couple of overrides: config from a
| package is merged one level deep, so half a section replaces the whole of it.
|
*/

return [

    'ssr' => [

        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),

    ],

    'ensure_pages_exist' => false,

    'page_paths' => [

        resource_path('js/pages'),

    ],

    'page_extensions' => [

        'js',
        'jsx',
        'svelte',
        'ts',
        'tsx',
        'vue',

    ],

    'use_script_element_for_initial_page' => (bool) env('INERTIA_USE_SCRIPT_ELEMENT_FOR_INITIAL_PAGE', false),

    'testing' => [

        'ensure_pages_exist' => true,

        'page_paths' => [

            resource_path('js/pages'),

        ],

        'page_extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

    'history' => [

        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),

    ],

];
