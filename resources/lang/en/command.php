<?php

return [
    'export' => [
        'description' => 'Export all published translations to CSV file.',
        'could_not_load_translation_file' => 'Could not load translation file: :file',
        'could_not_open_file' => 'Could not open file for writing: :path',
        'lang_directory_does_not_exist' => 'The lang directory does not exist at :path',
        'exported_translations' => 'Exported translations to  :path',
    ],
    'import' => [
        'description' => 'Update Laravel translations from a CSV file.',
        'csv_file_not_found' => 'CSV file not found at :path',
        'error_reading_csv_file' => 'Error reading CSV file at :path: :error',
        'failed_to_save_translations' => 'Failed to save translations for :path: :error',
        'imported_translations' => 'Imported translations from :path',
        'skipped_invalid_translation_path' => 'Skipped invalid translation path: :path',
    ],
];
