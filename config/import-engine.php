<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Batch Size
    |--------------------------------------------------------------------------
    | Number of rows to process per batch during import.
    */
    'default_batch_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Maximum Rows
    |--------------------------------------------------------------------------
    | Maximum number of rows allowed per import file.
    */
    'max_rows' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Enable Queue
    |--------------------------------------------------------------------------
    | When true, imports are dispatched as a background job.
    | Requires a running queue worker: php artisan queue:work
    */
    'enable_queue' => env('IMPORT_ENABLE_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    | When true, per-row errors are saved to the import_logs table.
    */
    'enable_logging' => env('IMPORT_ENABLE_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Models
    |--------------------------------------------------------------------------
    | Add models that can be targeted by an Import Profile.
    | Key = display name, Value = FQCN.
    */
    'allowed_models' => [
        'User'          => \App\Models\User::class,
        'Department'    => \App\Models\Department::class,
        'MasterItem'    => \App\Models\MasterItem::class,
        'Supplier'      => \App\Models\Supplier::class,
        'Unit'          => \App\Models\Unit::class,
        'ItemCategory'  => \App\Models\ItemCategory::class,
        'ItemType'      => \App\Models\ItemType::class,
        'Commodity'     => \App\Models\Commodity::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | All import routes will be prefixed with this value.
    */
    'route_prefix' => 'import',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | Middleware applied to all import routes.
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    | Disk path (relative to storage/app) where uploaded import files are saved.
    */
    'storage_path' => 'imports',

];
