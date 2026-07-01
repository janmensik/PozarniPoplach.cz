<?php

require_once __DIR__ . '/../../include/class.Version.php';

use PozarniPoplach\Version;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->tempChangelog = tempnam(sys_get_temp_dir(), 'CHANGELOG');
    $content = <<<EOD
# Changelog

## [1.2.3] - 2026-05-30
### Added
- New feature A
- New feature B

### Fixed
- Bug fix C

## [1.2.2] - 2026-05-20
### Changed
- Refactored D
EOD;
    file_put_contents($this->tempChangelog, $content);
});

afterEach(function () {
    if (file_exists($this->tempChangelog)) {
        unlink($this->tempChangelog);
    }
});

test('Version loads data correctly from file', function () {
    $version = new Version($this->tempChangelog);

    expect($version->versions)->toHaveCount(2);
    expect($version->versions)->toHaveKey('1.2.3');
    expect($version->versions)->toHaveKey('1.2.2');

    expect($version->versions['1.2.3']['date'])->toBe('2026-05-30');
    expect($version->versions['1.2.3']['data']['new'])->toContain('New feature A');
    expect($version->versions['1.2.3']['data']['bugfix'])->toContain('Bug fix C');
});

test('Version getCurrentVersion returns latest version', function () {
    $version = new Version($this->tempChangelog);
    expect($version->getCurrentVersion())->toBe('1.2.3');
});

test('Version returns 0.0.0 if file does not exist', function () {
    $version = new Version('/non/existent/file');
    expect($version->getCurrentVersion())->toBe('0.0.0');
});
