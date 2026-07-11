<?php

require_once __DIR__ . '/../../include/class.Unit.php';

use PozarniPoplach\Unit;
use Janmensik\Jmlib\Database;

// Subclass to bypass mysqli dependencies in unit tests
class UnitTestable extends Unit
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

    $this->unit = new Unit($this->db);
    $this->testableUnit = new UnitTestable($this->db);
});

test('Unit validation fails if fullname is empty', function () {
    $this->unit->data = ['fullname' => '', 'registration' => '123', 'category' => 'II'];
    $errors = $this->unit->validate();

    expect($errors)->toHaveKey('fullname');
    expect($errors['fullname'])->toBe('Fullname is required');
});

test('Unit validation fails if registration is empty', function () {
    $this->unit->data = ['fullname' => 'Test Unit', 'registration' => '', 'category' => 'II'];
    $errors = $this->unit->validate();

    expect($errors)->toHaveKey('registration');
    expect($errors['registration'])->toBe('Registration is required');
});

test('Unit validation fails if category is empty', function () {
    $this->unit->data = ['fullname' => 'Test Unit', 'registration' => '123', 'category' => ''];
    $errors = $this->unit->validate();

    expect($errors)->toHaveKey('category');
    expect($errors['category'])->toBe('Category is required');
});

test('Unit validation passes if all required fields are set', function () {
    $this->unit->data = [
        'fullname' => 'Valid Unit',
        'registration' => 'REG123',
        'category' => 'III'
    ];
    $errors = $this->unit->validate();
    expect($errors)->toBeEmpty();
});

test('Unit maps data from post correctly', function () {
    $postData = [
        'fullname' => 'Test Unit Post',
        'registration' => 'POST123',
        'category' => 'IV',
        'status' => 'ok',
        'region_id' => 5
    ];

    $this->testableUnit->mapFromPost($postData);

    expect($this->testableUnit->data['fullname'])->toBe('Test Unit Post');
    expect($this->testableUnit->data['registration'])->toBe('POST123');
    expect($this->testableUnit->data['category'])->toBe('IV');
    expect($this->testableUnit->data['status'])->toBe('ok');
    expect($this->testableUnit->data['region_id'])->toBe(5);
});

test('Unit getRegions returns data from DB', function () {
    $mockRegions = [
        ['id' => 1, 'RZPK' => 'A', 'title' => 'Praha'],
        ['id' => 2, 'RZPK' => 'S', 'title' => 'Středočeský']
    ];

    $this->db->expects($this->once())
             ->method('query')
             ->with($this->stringContains('SELECT id, RZPK, title FROM region'))
             ->willReturn('resource');

    $this->db->expects($this->once())
             ->method('getAllRows')
             ->with('resource')
             ->willReturn($mockRegions);

    $result = $this->unit->getRegions();
    expect($result)->toBe($mockRegions);
});
