<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | This configuration supports two methods for providing Firebase credentials:
    | 1. FIREBASE_CREDENTIALS_JSON environment variable (JSON string)
    | 2. Fallback to storage/app/firebase-auth.json file
    |
    | The credentials should contain the service account JSON from Firebase Console.
    |
    */

    'credentials' => env('FIREBASE_CREDENTIALS_JSON')
        ? json_decode(env('FIREBASE_CREDENTIALS_JSON'), true)
        : (file_exists(storage_path('app/firebase-auth.json'))
            ? json_decode(file_get_contents(storage_path('app/firebase-auth.json')), true)
            : null),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | The Firebase project ID can be specified separately or will be extracted
    | from the credentials JSON.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

];
