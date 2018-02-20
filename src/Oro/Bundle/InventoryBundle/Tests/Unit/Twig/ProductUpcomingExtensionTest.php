<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Twig;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\InventoryBundle\Twig\ProductUpcomingExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUpcomingExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @var ProductUpcomingExtension
     */
    protected $productUpcomingExtension;

    /**
     * @var ProductUpcomingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = $this->createMock(ProductUpcomingProvider::class);
        $this->productUpcomingExtension = new ProductUpcomingExtension($this->provider);
    }

    public function testIsUpcomingTrue()
    {
        $product = new Product();
        $this->provider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn(true);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_product_is_upcoming',
            [$product]
        );

        $this->assertTrue($result);
    }

    public function testIsUpcomingFalse()
    {
        $product = new Product();
        $this->provider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn(false);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_product_is_upcoming',
            [$product]
        );

        $this->assertFalse($result);
    }

    public function testGetAvailabilityDateEmpty()
    {
        $product = new Product();
        $this->provider->expects($this->once())->method('getAvailabilityDate')->with($product)
            ->willReturn(null);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_product_availability_date',
            [$product]
        );

        $this->assertNull($result);
    }

    public function testGetAvailabilityDate()
    {
        $product = new Product();
        $date = new \DateTime();
        $this->provider->expects($this->once())->method('getAvailabilityDate')->with($product)
            ->willReturn($date);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_product_availability_date',
            [$product]
        );

        $this->assertSame($date, $result);
    }
}
