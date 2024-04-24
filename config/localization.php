<?php

return [
    'locale' => 'en_US',

    'fallback_locale' => 'en_US',

    // Locale in POEditor => i18n locale for the project
    'locales' => [
        'en' => 'en_US',
        'de-ch' => 'de_CH',
        'de' => 'de_DE',
        'it' => 'it_IT',
    ],

    'custom_fallbacks' => [
        'de_DE' => 'de_CH'
    ],

    // Translations path name inside lang folder
    'translations_path_name' => 'i18n',

    // Packages path name inside lang folder
    'packages_path_name' => 'messages',

    // Translations path inside language path
    'translations_sub_path_name' => 'LC_MESSAGES',

    'domain' => 'messages',

    'encoding' => 'UTF-8',

    'session_prefix' => 'armcyber-localization',

    'extractor' => [
        // Source paths to scan.
        'source_paths' => [
            'app',
            'resources/views',
            'lang'
        ],

        // Keywords to extract.
        'keywords_list' => ['_t', '_', 'gettext', '_n:1,2', 'pgettext:1c,2', 'ngettext:1,2', 'dgettext:2'],
    ],

    // Download files with these extensions and save.
    'import_file_types' => [
        'po' => 'messages.po', // Type in PoEditor => Filename.
        'mo' => 'messages.mo',
    ],

    'poeditor' => [
        // Delete removed terms from PoEditor.
        'poeditor_delete_removed_terms' => false,

        // PoEditor API credentials
        'poeditor_api_token' => env('POEDITOR_API_TOKEN'),
        'poeditor_project_id' => env('POEDITOR_PROJECT_ID'),
    ]
];
