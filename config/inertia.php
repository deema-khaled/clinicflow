<?php

/*
 * Our pages live in resources/js/pages (lowercase), but Inertia's default is
 * js/Pages. On case-insensitive Windows/macOS disks that still works; on
 * Linux (CI, Docker) assertInertia() would fail to find the page files.
 */
return [

    'ensure_pages_exist' => false,

    'page_paths' => [
        resource_path('js/pages'),
    ],

    'page_extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],

    'testing' => [
        'ensure_pages_exist' => true,

        'page_paths' => [
            resource_path('js/pages'),
        ],

        'page_extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],
    ],

];
