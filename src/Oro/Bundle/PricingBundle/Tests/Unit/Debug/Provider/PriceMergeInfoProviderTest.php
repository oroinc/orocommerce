<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceMergeInfoProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\SelectedPriceProviderInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class PriceMergeInfoProviderTest extends TestCase
{
    use EntityTrait;

    private ConfigManager $configManager;
    private ManagerRegistry $registry;
    private ShardManager $shardManager;
    private PriceMergeInfoProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new PriceMergeInfoProvider(
            $this->configManager,
            $this->registry,
            $this->shardManager
        );
    }

    public function testGetPriceMergingDetails()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $pl = $this->getEntity(PriceList::class, ['id' => 2]);

        // Relation added 2 times to check that prices are not duplicated
        $relations = [
            (new CombinedPriceListToPriceList())
                ->setPriceList($pl)
                ->setMergeAllowed(false)
                ->setSortOrder(1),
            (new CombinedPriceListToPriceList())
                ->setPriceList($pl)
                ->setMergeAllowed(false)
                ->setSortOrder(1),
        ];

        $priceProvider1 = $this->createMock(SelectedPriceProviderInterface::class);
        $priceProvider1->expects($this->once())
            ->method('getSelectedPricesIds')
            ->with($relations, $product)
            ->willReturn(['id1']);

        $priceProvider2 = $this->createMock(SelectedPriceProviderInterface::class);
        $priceProvider2->expects($this->never())
            ->method('getSelectedPricesIds');
        $this->provider->addSelectedPriceProvider('minimal', $priceProvider1);
        $this->provider->addSelectedPriceProvider('merge_by_priority', $priceProvider2);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_strategy')
            ->willReturn('minimal');

        $price1 = $this->getEntity(ProductPrice::class, ['id' => 'id1']);
        $price1->setPrice(Price::create(100, 'USD'));
        $price1->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']));
        $price2 = $this->getEntity(ProductPrice::class, ['id' => 'id2']);
        $price2->setPrice(Price::create(150, 'EUR'));
        $price2->setUnit($this->getEntity(ProductUnit::class, ['code' => 'each']));

        $repo = $this->createMock(ProductPriceRepository::class);
        $repo->expects($this->once())
            ->method('findByPriceList')
            ->with(
                $this->shardManager,
                $pl,
                ['product' => $product],
                ['unit' => 'ASC', 'currency' => 'ASC', 'quantity' => 'ASC']
            )
            ->willReturn([
                $price1,
                $price2
            ]);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $expected = [
            '2' => [

                'USD' => [
                    'item' => [
                        [
                            'price' => $price1,
                            'is_selected' => true
                        ]
                    ]
                ],
                'EUR' => [
                    'each' => [
                        [
                            'price' => $price2,
                            'is_selected' => false
                        ]
                    ]
                ],
            ]
        ];

        $this->assertEquals($expected, $this->provider->getPriceMergingDetails($relations, $product));
    }

    public function testIsActualizationRequiredNoCpl()
    {
        $this->assertFalse($this->provider->isActualizationRequired(null, null, [], []));
    }

    public function testIsActualizationRequiredCplDifferent()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $activeCpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $this->assertFalse($this->provider->isActualizationRequired($cpl, $activeCpl, [], []));
    }

    public function testIsActualizationRequiredIsBuilding()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $activeCpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $repo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['combinedPriceList' => $activeCpl])
            ->willReturn($this->getEntity(CombinedPriceListBuildActivity::class));
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListBuildActivity::class)
            ->willReturn($repo);

        $this->assertFalse($this->provider->isActualizationRequired(
            $cpl,
            $activeCpl,
            [],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]]
        ));
    }

    /**
     * @dataProvider pricesDataProvider
     */
    public function testIsActualizationRequired(array $mergedPrices, array $currentPrices, bool $isEqual)
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        if (!$isEqual) {
            $repo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
            $repo->expects($this->once())
                ->method('findBy')
                ->with(['combinedPriceList' => $cpl])
                ->willReturn(null);
            $this->registry->expects($this->once())
                ->method('getRepository')
                ->with(CombinedPriceListBuildActivity::class)
                ->willReturn($repo);
        }

        $this->assertEquals(
            !$isEqual,
            $this->provider->isActualizationRequired($cpl, $cpl, $mergedPrices, $currentPrices)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function pricesDataProvider(): \Generator
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $item = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $each = $this->getEntity(ProductUnit::class, ['code' => 'each']);

        yield 'no_current_prices' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        ];

        yield 'no_selected_prices_empty_current' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => false
                            ]
                        ]
                    ]
                ]
            ],
            [],
            true
        ];

        yield 'no_prices' => [
            [],
            [],
            true
        ];

        yield 'no_merged_prices' => [
            [],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'no_selected_prices_not_empty_current' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => false
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_unit' => [
            [
                '1' => [
                    'USD' => [
                        'each' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $each),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_price' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 100, 'USD', 1, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_qty' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 10, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_currency' => [
            [
                '1' => [
                    'EUR' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'EUR', 1, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_number_of_merged' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => true
                            ],
                            [
                                'price' => $this->createPrice($cpl, 11, 'USD', 10, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            false
        ];

        yield 'different_number_of_current' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => true
                            ],
                            [
                                'price' => $this->createPrice($cpl, 11, 'USD', 10, $item),
                                'is_selected' => false
                            ]
                        ]
                    ]
                ]
            ],
            [
                'USD' => [
                    ['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')],
                    ['unitCode' => 'item', 'quantity' => 10, 'price' => Price::create(11, 'USD')]
                ]
            ],
            false
        ];

        yield 'same_prices' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            true
        ];

        yield 'same_prices_2' => [
            [
                '1' => [
                    'USD' => [
                        'item' => [
                            [
                                'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                                'is_selected' => true
                            ],
                            [
                                'price' => $this->createPrice($cpl, 11, 'USD', 10, $item),
                                'is_selected' => false
                            ]
                        ]
                    ]
                ]
            ],
            ['USD' => [['unitCode' => 'item', 'quantity' => 1, 'price' => Price::create(10, 'USD')]]],
            true
        ];
    }

    public function testGetUsedUnitsAndCurrencies()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $item = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $each = $this->getEntity(ProductUnit::class, ['code' => 'each']);
        $kg = $this->getEntity(ProductUnit::class, ['code' => 'kg']);

        $priceMergeInfo = [
            '1' => [
                'USD' => [
                    'item' => [
                        [
                            'price' => $this->createPrice($cpl, 10, 'USD', 1, $item),
                            'is_selected' => true
                        ]
                    ],
                    'each' => [
                        [
                            'price' => $this->createPrice($cpl, 10, 'USD', 1, $each),
                            'is_selected' => true
                        ]
                    ],
                ],
                'EUR' => [
                    'item' => [
                        [
                            'price' => $this->createPrice($cpl, 10, 'USD', 1, $each),
                            'is_selected' => true
                        ]
                    ]
                ]
            ],
            '2' => [
                'USD' => [
                    'item' => [
                        [
                            'price' => $this->createPrice($cpl, 11, 'USD', 1, $item),
                            'is_selected' => true
                        ]
                    ],
                    'kg' => [
                        [
                            'price' => $this->createPrice($cpl, 11, 'USD', 1, $kg),
                            'is_selected' => true
                        ]
                    ]
                ],
            ]
        ];

        $expected = [
            'item' => ['EUR', 'USD'],
            'each' => ['USD'],
            'kg' => ['USD']
        ];

        $this->assertEqualsCanonicalizing($expected, $this->provider->getUsedUnitsAndCurrencies($priceMergeInfo));
    }

    protected function createPrice(
        CombinedPriceList $pl,
        float $value,
        string $currency,
        float $qty,
        ProductUnit $unit
    ): CombinedProductPrice {
        return (new CombinedProductPrice())
            ->setPriceList($pl)
            ->setId(sprintf('pl%d-%d-%s', $pl->getId(), $qty, $unit->getCode()))
            ->setPrice(Price::create($value, $currency))
            ->setQuantity($qty)
            ->setUnit($unit);
    }
}
