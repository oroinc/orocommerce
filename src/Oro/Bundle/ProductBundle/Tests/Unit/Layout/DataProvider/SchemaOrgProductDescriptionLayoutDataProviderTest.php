<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SchemaOrgProductDescriptionLayoutDataProvider;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProviderInterface;

class SchemaOrgProductDescriptionLayoutDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private SchemaOrgProductDescriptionLayoutDataProvider $layoutDescriptionProvider;

    private Product $product;

    protected function setUp(): void
    {
        $this->product = new Product();
        $descriptionProvider = $this->createMock(SchemaOrgProductDescriptionProviderInterface::class);
        $descriptionProvider
            ->expects(self::once())
            ->method('getDescription')
            ->with($this->product)
            ->willReturn('test');

        $this->layoutDescriptionProvider = new SchemaOrgProductDescriptionLayoutDataProvider($descriptionProvider);
    }

    public function testGetDescription(): void
    {
        self::assertEquals('test', $this->layoutDescriptionProvider->getDescription($this->product));
    }
}
