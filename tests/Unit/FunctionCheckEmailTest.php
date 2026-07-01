<?php

require_once __DIR__ . '/../../lib/functions/function.check_email.php';

uses(\Tests\TestCase::class);

test('check_email returns truthy for valid email', function () {
    expect(check_email('user@example.com'))->toBeTruthy();
});

test('check_email returns truthy for email with subdomain', function () {
    expect(check_email('user@mail.example.com'))->toBeTruthy();
});

test('check_email returns truthy for email with plus sign', function () {
    expect(check_email('user+tag@example.com'))->toBeTruthy();
});

test('check_email returns falsy for email without domain', function () {
    expect(check_email('userexample.com'))->toBeFalsy();
});

test('check_email returns falsy for email without username', function () {
    expect(check_email('@example.com'))->toBeFalsy();
});

test('check_email returns falsy for email without TLD', function () {
    expect(check_email('user@example'))->toBeFalsy();
});

test('check_email returns falsy for empty string', function () {
    expect(check_email(''))->toBeFalsy();
});

test('check_email returns falsy for plain text', function () {
    expect(check_email('not-an-email'))->toBeFalsy();
});

test('check_email returns truthy for email with dots in local part', function () {
    expect(check_email('first.last@example.org'))->toBeTruthy();
});
