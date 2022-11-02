<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Visibility;

use Oro\Bundle\CatalogBundle\Visibility\BasicCategoryDefaultProductUnitOptionsVisibility;

class BasicCategoryDefaultProductUnitOptionsVisibilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicCategoryDefaultProductUnitOptionsVisibility
     */
    private $visibility;

    protected function setUp(): void
    {
        $this->visibility = new BasicCategoryDefaultProductUnitOptionsVisibility();
    }

    public function testIsDefaultUnitPrecisionSelectionAvailable()
    {
        $this->assertTrue($this->visibility->isDefaultUnitPrecisionSelectionAvailable());
    }
}
