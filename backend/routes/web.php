<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-broadcast', function () {
    $issue = App\Models\Issue::first();
    broadcast(new App\Events\IssueCreated($issue))->toOthers();
    return 'Event sent!';
});