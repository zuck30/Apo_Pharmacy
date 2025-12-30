<?php
// Add to the $middlewareAliases array
protected $middlewareAliases = [
    // ... existing aliases
    'dev.auth' => \App\Http\Middleware\DevelopmentAuth::class,
];