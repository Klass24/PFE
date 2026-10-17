<?php

namespace Tests\Unit;

use App\Core\Services\BaseService;
use PHPUnit\Framework\TestCase;

class BaseServiceTest extends TestCase
{
    private function filter(array $data, array $nullable = []): array
    {
        $service = new class extends BaseService {
            public function filter(array $data, array $nullable): array
            {
                return $this->filterData($data, $nullable);
            }
        };

        return $service->filter($data, $nullable);
    }

    public function test_optional_null_values_are_removed(): void
    {
        $this->assertSame(['name' => 'Vehicle'], $this->filter([
            'name' => 'Vehicle',
            'description' => null,
        ]));
    }

    public function test_explicitly_nullable_fields_are_preserved(): void
    {
        $this->assertSame(['description' => null], $this->filter([
            'description' => null,
            'name' => null,
        ], ['description']));
    }

    public function test_falsy_non_null_values_are_preserved(): void
    {
        $data = ['is_active' => false, 'mileage' => 0, 'notes' => '', 'tags' => []];

        $this->assertSame($data, $this->filter($data));
    }

    public function test_empty_data_remains_empty(): void
    {
        $this->assertSame([], $this->filter([], ['description']));
    }
}