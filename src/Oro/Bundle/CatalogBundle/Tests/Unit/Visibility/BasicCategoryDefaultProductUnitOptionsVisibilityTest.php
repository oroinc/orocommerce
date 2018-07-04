<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

class BasicCategoryDefaultProductUnitOptionsVisibilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicCategoryDefaultProductUnitOptionsVisibility
     */
    private $visibility;

    public function setUp()
    {
        $this->visibility = new BasicCategoryDefaultProductUnitOptionsVisibility();
    }

    public function testIsDefaultUnitPrecisionSelectionAvailable()
    {
        $this->assertTrue($this->visibility->isDefaultUnitPrecisionSelectionAvailable());
    }
}
