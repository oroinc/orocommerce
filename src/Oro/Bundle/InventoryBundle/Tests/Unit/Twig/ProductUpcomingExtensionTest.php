<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Twig;

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
     * @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->provider = $this->createMock(UpcomingProductProvider::class);
        $this->productUpcomingExtension = new ProductUpcomingExtension($this->provider);
    }

    /**
     * @dataProvider isUpcomingDataProvider
     */
    public function testIsUpcomingProduct(bool $expected, bool $isUpcoming): void
    {
        $product = new Product();
        $this->provider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn($isUpcoming);

        $result = self::callTwigFunction(
            $this->productUpcomingExtension,
            'oro_inventory_is_product_upcoming',
            [$product]
        );

        self::assertEquals($expected, $result);
    }

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

    /**
     * @dataProvider upcomingAvailabilityDateDataProvider
     */
    public function testGetUpcomingAvailabilityDate(?\DateTime $expected, ?\DateTime $availabilityDate): void
    {
        $product = new Product();
        $this->provider
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
