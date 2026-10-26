<?php

test('the application boots and returns a successful response', function () {
    $this->get('/')->assertOk();
});

test('the application has the correct app name', function () {
    expect(config('app.name'))->toBe('ClothesTed');
});
