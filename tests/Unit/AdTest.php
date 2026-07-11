<?php

require_once __DIR__ . '/../../include/class.Ad.php';

use PozarniPoplach\Ad;
use Janmensik\Jmlib\Database;

// Subclass to bypass mysqli dependencies in unit tests
class AdTestable extends Ad
{
    public function sanitize($value = null, $type = 'text', $required = false, $extra_data = null)
    {
        return $value;
    }

    /**
     * Overridden to avoid mysqli_real_escape_string during unit tests
     */
    public function setter(?int $int = null): bool|int
    {
        $set = [];
        foreach ($this->elements as $el) {
            if (isset($this->data[$el])) {
                $value = $this->data[$el];
                $set[$el] = $value === null ? 'NULL' : '"' . addslashes((string)$value) . '"';
            }
        }
        return ($this->set($set, $int));
    }
}

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // We still need a dummy mysqli for type hinting if any other method calls it
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->ad = new Ad($this->db);
    $this->testableAd = new AdTestable($this->db);
});

test('Ad validation fails if status is empty', function () {
    $this->ad->data = ['status' => ''];
    $errors = $this->ad->validate();

    expect($errors)->toHaveKey('status');
    expect($errors['status'])->toBe('Status is required');
});

test('Ad validation passes if status is set', function () {
    $this->ad->data = ['status' => 'active'];
    $errors = $this->ad->validate();

    expect($errors)->not->toHaveKey('status');
});

test('Ad maps data from post correctly', function () {
    $postData = [
        'title' => 'Test Ad',
        'status' => 'active',
        'ad_text' => 'Some text',
        'promo_code' => 'PROMO123',
        'target_link' => 'https://example.com',
        'advertiser_id' => 1
    ];

    $this->testableAd->mapFromPost($postData);

    expect($this->testableAd->data['title'])->toBe('Test Ad');
    expect($this->testableAd->data['status'])->toBe('active');
    expect($this->testableAd->data['ad_text'])->toBe('Some text');
    expect($this->testableAd->data['promo_code'])->toBe('PROMO123');
    expect($this->testableAd->data['target_link'])->toBe('https://example.com');
    expect($this->testableAd->data['advertiser_id'])->toBe(1);
});

test('Ad maps banner_image_url correctly', function () {
    $postData = [
        'banner_image_url' => 'https://example.com/image.jpg'
    ];

    $this->testableAd->mapFromPost($postData);

    expect($this->testableAd->data['banner_image_url'])->toBe('https://example.com/image.jpg');
});

test('Ad setter includes banner_image_url in SQL', function () {
    $this->testableAd->data = [
        'status' => 'active',
        'banner_image_url' => 'https://example.com/image.jpg'
    ];

    $this->db->expects($this->once())
             ->method('query')
             ->with($this->callback(function ($sql) {
                 return str_contains($sql, 'banner_image_url') && str_contains($sql, 'https://example.com/image.jpg');
             }));

    $this->db->method('getNumAffected')
             ->willReturn(1);

    $this->db->method('getId')
             ->willReturn(123);

    $result = $this->testableAd->setter();
    expect($result)->toBe(123);
});
