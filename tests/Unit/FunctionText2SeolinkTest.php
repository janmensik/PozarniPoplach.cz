<?php

require_once __DIR__ . '/../../lib/functions/function.text2seolink.php';

uses(\Tests\TestCase::class);

test('text2seolink converts simple string to lowercase slug', function () {
    expect(text2seolink('Hello World'))->toBe('hello-world');
});

test('text2seolink removes leading and trailing dashes', function () {
    $result = text2seolink('!Hello World!');
    expect($result)->not->toStartWith('-');
    expect($result)->not->toEndWith('-');
});

test('text2seolink collapses multiple consecutive dashes into one', function () {
    $result = text2seolink('Hello - World - Test');
    expect($result)->not->toContain('--');
});

test('text2seolink handles special characters and punctuation', function () {
    $result = text2seolink('Hello World! - Go hell.');
    expect($result)->toBe('hello-world-go-hell');
});

test('text2seolink returns only alphanumeric and dash characters', function () {
    $result = text2seolink('Test@Email.com');
    expect($result)->toMatch('/^[a-z0-9\-]+$/');
});

test('text2seolink handles already slug-like input', function () {
    expect(text2seolink('already-a-slug'))->toBe('already-a-slug');
});

test('text2seolink converts numbers', function () {
    $result = text2seolink('Article 42');
    expect($result)->toBe('article-42');
});
