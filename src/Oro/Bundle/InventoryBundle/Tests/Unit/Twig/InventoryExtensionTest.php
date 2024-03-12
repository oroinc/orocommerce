<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\InventoryStatusProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Twig\InventoryExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InventoryExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private UpcomingProductProvider|MockObject $upcomingProductProvider;
    private LowInventoryProvider|MockObject $lowInventoryProvider;
    private InventoryStatusProvider|MockObject $inventoryStatusProvider;
    private InventoryExtension $extension;

    protected function setUp(): void
    {
        $this->upcomingProductProvider = $this->createMock(UpcomingProductProvider::class);
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);
        $this->inventoryStatusProvider = $this->createMock(InventoryStatusProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_inventory.provider.upcoming_product_provider', $this->upcomingProductProvider)
            ->add('oro_inventory.inventory.low_inventory_provider', $this->lowInventoryProvider)
            ->add('oro_inventory.provider.inventory_status', $this->inventoryStatusProvider)
            ->getContainer($this);

        $this->extension = new InventoryExtension($container);
    }

    /**
     * @dataProvider isUpcomingDataProvider
     */
    public function testIsUpcomingProduct(bool $expected, bool $isUpcoming): void
    {
        $product = new Product();
        $this->upcomingProductProvider->expects($this->once())
            ->method('isUpcoming')
            ->with($this->identicalTo($product))
            ->willReturn($isUpcoming);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_inventory_is_product_upcoming', [$product])
        );
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
        $this->upcomingProductProvider->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($this->identicalTo($product))
            ->willReturn($availabilityDate);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_inventory_upcoming_product_availability_date', [$product])
        );
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

    /**
     * @dataProvider isLowInventoryDataProvider
     */
    public function testIsLowInventory(bool $lowInventory)
    {
        $product = new Product();
        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryProduct')
            ->with($this->identicalTo($product))
            ->willReturn($lowInventory);

        $this->assertSame(
            $lowInventory,
            self::callTwigFunction($this->extension, 'oro_is_low_inventory_product', [$product])
        );
    }

    public function isLowInventoryDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * @dataProvider invStatusDataProvider
     */
    public function testGetInventoryStatusCodeAndLabel(Product|ProductView|array $data)
    {
        $this->inventoryStatusProvider
            ->expects(self::once())
            ->method('getCode')
            ->with($data)
            ->willReturn('code');

        $this->inventoryStatusProvider
            ->expects(self::once())
            ->method('getLabel')
            ->with($data)
            ->willReturn('label');

        $this->assertSame(
            'code',
            self::callTwigFunction($this->extension, 'oro_inventory_status_code', [$data])
        );
        $this->assertSame(
            'label',
            self::callTwigFunction($this->extension, 'oro_inventory_status_label', [$data])
        );
    }

    public function invStatusDataProvider(): \Generator
    {
        yield [
            'data' => new Product(),
        ];
        yield [
            'data' => new ProductView(),
        ];
        yield [
            'data' => ['id' => 1], // Search result item
        ];
    }
}
