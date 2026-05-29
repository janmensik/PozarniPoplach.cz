<?php

require_once __DIR__ . '/../../include/class.Advertiser.php';

use PozarniPoplach\Advertiser;
use Janmensik\Jmlib\Database;

// Subclass to bypass mysqli dependencies in unit tests
class AdvertiserTestable extends Advertiser {
    public function sanitize($value = null, $type = 'text', $required = false, $extra_data = null) {
        return $value;
    }

    public function setter(?int $int = null): bool|int {
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
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();
                         
    $this->advertiser = new Advertiser($this->db);
    $this->testableAdvertiser = new AdvertiserTestable($this->db);
});

test('Advertiser validation fails if name or email is empty', function () {
    $this->advertiser->data = ['name' => '', 'contact_email' => ''];
    $errors = $this->advertiser->validate();
    
    expect($errors)->toHaveKey('name');
    expect($errors)->toHaveKey('contact_email');
});

test('Advertiser validation passes if all required fields are set', function () {
    $this->advertiser->data = ['name' => 'John Doe', 'contact_email' => 'john@example.com'];
    $errors = $this->advertiser->validate();
    
    expect($errors)->toBeEmpty();
});

test('Advertiser maps data from post correctly', function () {
    $postData = [
        'name' => 'Acme Corp',
        'contact_email' => 'contact@acme.com'
    ];
    
    $this->testableAdvertiser->mapFromPost($postData);
    
    expect($this->testableAdvertiser->data['name'])->toBe('Acme Corp');
    expect($this->testableAdvertiser->data['contact_email'])->toBe('contact@acme.com');
});

test('Advertiser setter calls DB query', function () {
    $this->testableAdvertiser->data = [
        'name' => 'New Advertiser',
        'contact_email' => 'new@example.com'
    ];
    
    $this->db->expects($this->once())->method('query');
    $this->db->method('getNumAffected')->willReturn(1);
    $this->db->method('getId')->willReturn(456);
    
    $result = $this->testableAdvertiser->setter();
    
    expect($result)->toBe(456);
});
