<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Provider\ProductKitItemUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Twig\ProductKitItemExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ProductKitItemUnitPrecisionProvider|MockObject $kitItemUnitPrecisionProvider;

    private ProductKitItemExtension $extension;

    protected function setUp(): void
    {
        $this->kitItemUnitPrecisionProvider = $this->createMock(ProductKitItemUnitPrecisionProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.provider.product_kit_item_unit_precision', $this->kitItemUnitPrecisionProvider)
            ->getContainer($this);

        $this->extension = new ProductKitItemExtension($container);
    }

    public function testGetProductKitItemUnitPrecision(): void
    {
        $kitItem = new ProductKitItem();
        $expectedResult = 3;

        $this->kitItemUnitPrecisionProvider
            ->expects(self::once())
            ->method('getUnitPrecisionByKitItem')
            ->with($kitItem)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_product_kit_item_unit_precision', [$kitItem])
        );
    }
}
