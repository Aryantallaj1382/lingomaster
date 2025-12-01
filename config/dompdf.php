<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => false,   // Throw an Exception on warnings from dompdf

    'public_path' => null,  // Override the public path if needed

    /*
     * Dejavu Sans font is missing glyphs for converted entities, turn it off if you need to show € and £.
     */
    'convert_entities' => true,

    'options' => [
        'default_font' => 'vazirmatn',

        'custom_font_dir' => resource_path('fonts/'),
        'custom_font_data' => [
            'vazirmatn' => [
                'R' => 'Vazirmatn-Regular.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ],

        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'chroot' => realpath(base_path()),
        'enable_remote' => true,
        'dpi' => 96,
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        'enable_php' => false,
        'enable_javascript' => true,
        'enable_font_subsetting' => false,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],

];
