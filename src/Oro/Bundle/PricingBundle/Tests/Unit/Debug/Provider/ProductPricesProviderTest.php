<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\ProductPricesProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPricesProviderTest extends TestCase
{
    use EntityTrait;

    private DebugProductPricesPriceListRequestHandler|MockObject $requestHandler;
    private ManagerRegistry|MockObject $registry;
    private ShardManager|MockObject $shardManager;
    private ProductPricesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(DebugProductPricesPriceListRequestHandler::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new ProductPricesProvider(
            $this->requestHandler,
            $this->registry,
            $this->shardManager
        );
    }

    public function testGetCurrentPricesNoPriceList()
    {
        $this->requestHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn(null);

        $product = new Product();

        $this->assertEquals([], $this->provider->getCurrentPrices($product));
    }

    public function testGetCurrentPrices()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 10]);
        $product = $this->getEntity(Product::class, ['id' => 20]);

        $this->requestHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($cpl);

        $prices = [
            new ProductPriceDTO(
                $product,
                Price::create(10.0, 'USD'),
                10,
                $this->getEntity(ProductUnit::class, ['code' => 'item'])
            ),
            new ProductPriceDTO(
                $product,
                Price::create(12.0, 'EUR'),
                1,
                $this->getEntity(ProductUnit::class, ['code' => 'item'])
            ),
            new ProductPriceDTO(
                $product,
                Price::create(11.0, 'USD'),
                1,
                $this->getEntity(ProductUnit::class, ['code' => 'item'])
            )
        ];

        $repo = $this->createMock(CombinedProductPriceRepository::class);
        $repo->expects($this->once())
            ->method('getPricesBatch')
            ->with(
                $this->shardManager,
                10,
                [20]
            )
            ->willReturn($prices);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedProductPrice::class)
            ->willReturn($repo);

        $this->assertEquals(
            [
                'USD' => [
                    [
                        'price' => Price::create(11.0, 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ],
                    [
                        'price' => Price::create(10.0, 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 10,
                    ]
                ],
                'EUR' => [
                    [
                        'price' => Price::create(12.0, 'EUR'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ]
                ]
            ],
            $this->provider->getCurrentPrices($product)
        );
    }
}
