<?php

namespace Tests\Unit;

use App\Modules\Vehicle\Services\VehicleCatalog;
use PHPUnit\Framework\TestCase;

class VehicleCatalogTest extends TestCase
{
    public function test_brands_are_sorted(): void
    {
        $brands = VehicleCatalog::brands();
        $sortedBrands = $brands;
        sort($sortedBrands);

        $this->assertSame($sortedBrands, $brands);
    }

    public function test_models_match_brand_labels_without_case_or_outer_whitespace(): void
    {
        $this->assertSame(
            VehicleCatalog::modelsFor('Toyota'),
            VehicleCatalog::modelsFor('  tOyOtA  ')
        );
    }

    public function test_unknown_brand_has_no_models(): void
    {
        $this->assertSame([], VehicleCatalog::modelsFor('Unknown brand'));
    }
}
