<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Twig;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Twig\ProductUpcomingExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUpcomingExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @var ProductUpcomingExtension
     */
    protected $productUpcomingExtension;

    /**
     * @var ProductUpcomingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $upcomingProductProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = $this->createMock(ProductUpcomingProvider::class);
        $this->upcomingProductProvider = $this->createMock(UpcomingProductProvider::class);
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

    public function testIsUpcomingProductWhenNoUpcomingProductProviderSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Upcoming product provider is not set for Oro\Bundle\InventoryBundle\Twig\ProductUpcomingExtension'
        );

        self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_is_product_upcoming',
            [new Product()]
        );
    }

    /**
     * @dataProvider isUpcomingDataProvider
     * @param bool $expected
     * @param bool $isUpcoming
     */
    public function testIsUpcomingProduct(bool $expected, bool $isUpcoming): void
    {
        $this->productUpcomingExtension->setUpcomingProductProvider($this->upcomingProductProvider);

        $product = new Product();
        $this->upcomingProductProvider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn($isUpcoming);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_is_product_upcoming',
            [$product]
        );

        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function isUpcomingDataProvider(): array
    {
        return [
            'product is upcoming' => [
                'expected' => true,
                'isUpcoming' => true
            ],
            'product is not upcoming' => [
                'expected' => false,
                'isUpcoming' => false
            ],
        ];
    }

    public function testGetUpcomingAvailabilityDateWhenNoUpcomingProductProviderSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Upcoming product provider is not set for Oro\Bundle\InventoryBundle\Twig\ProductUpcomingExtension'
        );

        self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_upcoming_product_availability_date',
            [new Product()]
        );
    }

    /**
     * @dataProvider upcomingAvailabilityDateDataProvider
     * @param \DateTime|null $expected
     * @param \DateTime|null $availabilityDate
     */
    public function testGetUpcomingAvailabilityDate(?\DateTime $expected, ?\DateTime $availabilityDate): void
    {
        $this->productUpcomingExtension->setUpcomingProductProvider($this->upcomingProductProvider);

        $product = new Product();
        $this->upcomingProductProvider
            ->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn($availabilityDate);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_upcoming_product_availability_date',
            [$product]
        );

        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function upcomingAvailabilityDateDataProvider(): array
    {
        $date = new \DateTime('2001-01-03');

        return [
            'empty upcoming availability date' => [
                'expected' => null,
                'availabilityDate' => null
            ],
            'not empty upcoming availability date' => [
                'expected' => $date,
                'availabilityDate' => $date
            ]
        ];
    }
}
