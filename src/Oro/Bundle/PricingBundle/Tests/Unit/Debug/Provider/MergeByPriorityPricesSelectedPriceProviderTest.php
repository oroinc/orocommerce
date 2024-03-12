<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Debug\Provider\MergeByPriorityPricesSelectedPriceProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MergeByPriorityPricesSelectedPriceProviderTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $registry;
    private ShardManager|MockObject $shardManager;
    private MergeByPriorityPricesSelectedPriceProvider $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new MergeByPriorityPricesSelectedPriceProvider(
            $this->registry,
            $this->shardManager
        );
    }

    /**
     * @dataProvider getRelationsDataProvider
     */
    public function testGetSelectedPricesIds(array $relations, array $prices, array $expectedIds)
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $repo = $this->createMock(ProductPriceRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $map = [];
        for ($i = 0; $i < count($relations); $i++) {
            $map[] = [
                $this->shardManager,
                $relations[$i]->getPriceList(),
                ['product' => $product],
                [],
                null,
                null,
                $prices[$i]
            ];
        }

        $repo->expects($this->any())
            ->method('findByPriceList')
            ->willReturnMap($map);

        $ids = $this->provider->getSelectedPricesIds($relations, $product);
        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRelationsDataProvider(): \Generator
    {
        $item = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $each = $this->getEntity(ProductUnit::class, ['code' => 'each']);

        $pl1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $pl2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $pl3 = $this->getEntity(PriceList::class, ['id' => 3]);
        $pl4 = $this->getEntity(PriceList::class, ['id' => 4]);

        $pl1Prices = [
            $this->createPrice($pl1, 10, 'USD', 1, $item)
        ];
        $pl2Prices = [
            $this->createPrice($pl2, 10, 'USD', 1, $each),
            $this->createPrice($pl2, 9, 'USD', 10, $item),
        ];
        $pl3Prices = [
            $this->createPrice($pl3, 9, 'USD', 1, $item),
            $this->createPrice($pl3, 8, 'USD', 10, $item),
            $this->createPrice($pl3, 7, 'USD', 100, $item),
            $this->createPrice($pl3, 9, 'USD', 1, $each),
            $this->createPrice($pl3, 8, 'USD', 10, $each),
        ];
        $pl4Prices = [
            $this->createPrice($pl4, 8, 'USD', 1, $item),
            $this->createPrice($pl4, 6, 'USD', 1000, $item),
            $this->createPrice($pl4, 8, 'USD', 1, $each),
            $this->createPrice($pl4, 6, 'USD', 1000, $each),
        ];

        yield [
            [
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl1)
                    ->setMergeAllowed(false)
                    ->setSortOrder(1),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl2)
                    ->setMergeAllowed(true)
                    ->setSortOrder(2),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl3)
                    ->setMergeAllowed(false)
                    ->setSortOrder(3),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl4)
                    ->setMergeAllowed(true)
                    ->setSortOrder(4)
            ],
            [$pl1Prices, $pl2Prices, $pl3Prices, $pl4Prices],
            ['pl1-1-item']
        ];

        yield [
            [
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl1)
                    ->setMergeAllowed(true)
                    ->setSortOrder(1),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl2)
                    ->setMergeAllowed(true)
                    ->setSortOrder(2),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl3)
                    ->setMergeAllowed(false)
                    ->setSortOrder(3),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl4)
                    ->setMergeAllowed(true)
                    ->setSortOrder(4)
            ],
            [$pl1Prices, $pl2Prices, $pl3Prices, $pl4Prices],
            [
                'pl1-1-item',
                'pl2-10-item',
                'pl2-1-each',
                'pl4-1000-item',
                'pl4-1000-each'
            ]
        ];

        yield [
            [
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl1)
                    ->setMergeAllowed(true)
                    ->setSortOrder(1),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl2)
                    ->setMergeAllowed(true)
                    ->setSortOrder(2),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl3)
                    ->setMergeAllowed(true)
                    ->setSortOrder(3),
                (new CombinedPriceListToPriceList())
                    ->setPriceList($pl4)
                    ->setMergeAllowed(true)
                    ->setSortOrder(4)
            ],
            [$pl1Prices, $pl2Prices, $pl3Prices, $pl4Prices],
            [
                'pl1-1-item',
                'pl2-10-item',
                'pl2-1-each',
                'pl3-100-item',
                'pl3-10-each',
                'pl4-1000-item',
                'pl4-1000-each'
            ]
        ];
    }

    protected function createPrice(
        PriceList $pl,
        float $value,
        string $currency,
        float $qty,
        ProductUnit $unit
    ): ProductPrice {
        return (new ProductPrice())
            ->setPriceList($pl)
            ->setId(sprintf('pl%d-%d-%s', $pl->getId(), $qty, $unit->getCode()))
            ->setPrice(Price::create($value, $currency))
            ->setQuantity($qty)
            ->setUnit($unit);
    }
}
