<?php

return [
    'allowed_github_ids' => array_values(array_filter(array_map('trim', explode(',', env('ALLOWED_GITHUB_IDS', ''))))),
    'github_read_token' => env('GITHUB_READ_TOKEN'),
];
