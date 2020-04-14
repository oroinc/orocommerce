<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

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
