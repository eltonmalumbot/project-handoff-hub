<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

/** Test-only router. Never use as the production entry point. */
if (getenv('APP_ENV') !== 'testing' || getenv('BROWSER_TEST') !== '1') {
    http_response_code(404);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__.'/../../public'.$path)) {
    return false;
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->booted(function () {
    config(['services.github.client_id' => 'browser-client', 'services.github.client_secret' => 'browser-secret', 'services.github.redirect' => 'http://127.0.0.1:8000/auth/github/callback']);
    $provider = Socialite::driver('github')->setScopes([]);
    $provider->setHttpClient(new Client(['handler' => HandlerStack::create(new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'test-only-token', 'token_type' => 'bearer', 'scope' => ''])),
        new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 118357175, 'login' => 'eltonmalumbot', 'name' => 'Elton Malumbot', 'node_id' => 'browser-test', 'avatar_url' => '', 'email' => null])),
    ]))]));
    if (getenv('BROWSER_GITHUB_FAKE') === '1') {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 404)]);
    }
});
$app->handleRequest(Request::capture());
