<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\PriceAwareShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingPriceProviderTest extends TestCase
{
    use EntityTrait;
    use MemoryCacheProviderAwareTestTrait;

    private MethodsConfigsRulesByContextProviderInterface|MockObject $shippingRulesProvider;

    private ShippingPriceCache|MockObject $priceCache;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private ShippingPriceProvider $shippingPriceProvider;

    protected function setUp(): void
    {
        $this->shippingRulesProvider = $this->createMock(MethodsConfigsRulesByContextProviderInterface::class);
        $this->priceCache = $this->createMock(ShippingPriceCache::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $methods = [
            'flat_rate' => $this->getEntity(ShippingMethodStub::class, [
                'identifier' => 'flat_rate',
                'sortOrder' => 1,
                'types' => [
                    'primary' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'primary',
                        'sortOrder' => 1,
                    ])
                ]
            ]),
            'integration_method' => $this->getEntity(PriceAwareShippingMethodStub::class, [
                'identifier' => 'integration_method',
                'sortOrder' => 2,
                'isGrouped' => true,
                'types' => [
                    'ground' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'ground',
                        'sortOrder' => 1,
                    ]),
                    'air' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'air',
                        'sortOrder' => 2,
                    ])
                ]
            ])
        ];

        $shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $shippingMethodProvider->expects($this->any())
            ->method('getShippingMethod')
            ->willReturnCallback(function ($methodId) use ($methods) {
                return $methods[$methodId] ?? null;
            });

        $this->shippingPriceProvider = new ShippingPriceProvider(
            $this->shippingRulesProvider,
            $shippingMethodProvider,
            $this->priceCache,
            new ShippingMethodViewFactory($shippingMethodProvider),
            $this->eventDispatcher
        );
    }

    public function testGetApplicablePaymentMethodsWhenCache(): void
    {
        $methodViews = $this->createMock(ShippingMethodViewCollection::class);
        $this->mockMemoryCacheProvider($methodViews);
        $this->setMemoryCacheProvider($this->shippingPriceProvider);

        $this->assertEquals(
            $methodViews,
            $this->shippingPriceProvider->getApplicableMethodsViews($this->createMock(ShippingContextInterface::class))
        );
    }

    /**
     * @dataProvider getApplicableShippingMethodsConfigsRulesProvider
     */
    public function testGetApplicableMethodsViews(array $shippingRules, array $expectedData): void
    {
        $shippingLineItems = [new ShippingLineItem([])];

        $sourceEntity = new \stdClass();

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD',
            ShippingContext::FIELD_SOURCE_ENTITY => $sourceEntity
        ]);

        $this->shippingRulesProvider->expects($this->once())
            ->method('getShippingMethodsConfigsRules')
            ->with($context)
            ->willReturn($shippingRules);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ApplicableMethodsEvent::class), ApplicableMethodsEvent::NAME);

        $this->assertEquals(
            $expectedData,
            $this->shippingPriceProvider
                ->getApplicableMethodsViews($context)
                ->toArray()
        );
    }

    /**
     * @dataProvider getApplicableShippingMethodsConfigsRulesProvider
     */
    public function testGetApplicableMethodsViewsWhenMemoryCacheProvider(array $shippingRules, array $expectedData)
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->shippingPriceProvider);

        $this->testGetApplicableMethodsViews($shippingRules, $expectedData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getApplicableShippingMethodsConfigsRulesProvider(): array
    {
        return [
            'one rule' => [
                'shippingRule' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 111,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => Price::create(12, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 222,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'aware_price' => null,
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 333,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'unknown_method',
                            ])
                        ]
                    ])
                ],
                'expectedData' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'label' => 'flat_rate.label',
                        'sortOrder' => 1,
                        'isGrouped' => false,
                        'types' => [
                            'primary' => [
                                'identifier' => 'primary',
                                'label' => 'primary.label',
                                'sortOrder' => 1,
                                'price' => Price::create(12, 'USD')
                            ]
                        ]
                    ]
                ]
            ],
            'several rules with same methods ans diff types' => [
                'shippingRule' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 1234,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'aware_price' => Price::create(2, 'USD'),
                                        ],
                                    ]),
                                ],
                            ]),
                        ]
                    ]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 2345,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => Price::create(1, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 4567,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'aware_price' => Price::create(3, 'USD'),
                                        ],
                                    ]),
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'air',
                                        'options' => [
                                            'aware_price' => Price::create(4, 'USD'),
                                        ],
                                    ]),
                                ],
                            ]),
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => Price::create(5, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                ],
                'expectedData' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'label' => 'flat_rate.label',
                        'sortOrder' => 1,
                        'isGrouped' => false,
                        'types' => [
                            'primary' => [
                                'identifier' => 'primary',
                                'label' => 'primary.label',
                                'sortOrder' => 1,
                                'price' => Price::create(1, 'USD'),
                            ]
                        ]
                    ],
                    'integration_method' => [
                        'identifier' => 'integration_method',
                        'label' => 'integration_method.label',
                        'sortOrder' => 2,
                        'isGrouped' => true,
                        'types' => [
                            'ground' => [
                                'identifier' => 'ground',
                                'label' => 'ground.label',
                                'sortOrder' => 1,
                                'price' => Price::create(2, 'USD'),
                            ],
                            'air' => [
                                'identifier' => 'air',
                                'label' => 'air.label',
                                'sortOrder' => 2,
                                'price' => Price::create(4, 'USD'),
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testGetApplicableMethodsViewsCache(): void
    {
        $shippingLineItems = [new ShippingLineItem([])];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $ruleId = 111;
        $this->shippingRulesProvider->expects(self::exactly(2))
            ->method('getShippingMethodsConfigsRules')
            ->with($context)
            ->willReturn([
                $this->getEntity(ShippingMethodsConfigsRule::class, [
                    'id' => $ruleId,
                    'methodConfigs' => [
                        $this->getEntity(ShippingMethodConfig::class, [
                            'method' => 'flat_rate',
                            'typeConfigs' => [
                                $this->getEntity(ShippingMethodTypeConfig::class, [
                                    'enabled' => true,
                                    'type' => 'primary',
                                    'options' => [
                                        'price' => Price::create(1, 'USD'),
                                    ],
                                ])
                            ],
                        ])
                    ]
                ])
            ]);
        $price = Price::create(1, 'USD');

        $expectedData = [
            'flat_rate' => [
                'identifier' => 'flat_rate',
                'label' => 'flat_rate.label',
                'sortOrder' => 1,
                'isGrouped' => false,
                'types' => [
                    'primary' => [
                        'identifier' => 'primary',
                        'label' => 'primary.label',
                        'sortOrder' => 1,
                        'price' => $price
                    ]
                ]
            ]
        ];

        $this->priceCache->expects(self::exactly(2))
            ->method('hasRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturnOnConsecutiveCalls(false, true);
        $this->priceCache->expects(self::once())
            ->method('saveRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId, Price::create(1, 'USD'))
            ->willReturn(true);
        $this->priceCache->expects(self::once())
            ->method('getRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturn(Price::create(2, 'USD'));

        $this->assertEquals(
            $expectedData,
            $this->shippingPriceProvider->getApplicableMethodsViews($context)->toArray()
        );
        $price->setValue(2);
        $this->assertEquals(
            $expectedData,
            $this->shippingPriceProvider->getApplicableMethodsViews($context)->toArray()
        );
    }

    /**
     * @dataProvider getPriceDataProvider
     */
    public function testGetPrice(string $methodId, string $typeId, array $shippingRules, Price $expectedPrice = null)
    {
        $shippingLineItems = [new ShippingLineItem([])];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->shippingRulesProvider->expects($this->once())
            ->method('getShippingMethodsConfigsRules')
            ->with($context)
            ->willReturn($shippingRules);

        $this->priceCache->expects($this->exactly($expectedPrice ? 1 : 0))
            ->method('saveRulePrice');

        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getPriceDataProvider(): array
    {
        return [
            'no rule' => [
                'methodId' => 'integration_method',
                'typeId' => 'ground',
                'shippingRules' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 111,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => Price::create(12, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                ],
                'expectedData' => null,
            ],
            'one rule' => [
                'methodId' => 'flat_rate',
                'typeId' => 'primary',
                'shippingRules' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 222,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => Price::create(12, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                ],
                'expectedData' => Price::create(12, 'USD'),
            ],
            'no price' => [
                'methodId' => 'flat_rate',
                'typeId' => 'primary',
                'shippingRules' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 333,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'primary',
                                        'options' => [
                                            'price' => null
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ]),
                ],
                'expectedData' => null,
            ],
            'several rules with same methods ans types' => [
                'methodId' => 'integration_method',
                'typeId' => 'ground',
                'shippingRules' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 444,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'price' => Price::create(1, 'USD'),
                                        ],
                                    ])
                                ],
                            ]),
                        ]
                    ]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, [
                        'id' => 1234,
                        'methodConfigs' => [
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'price' => Price::create(2, 'USD'),
                                        ],
                                    ])
                                ],
                            ]),
                            $this->getEntity(ShippingMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'air',
                                        'options' => [
                                            'price' => Price::create(3, 'USD'),
                                        ],
                                    ])
                                ],
                            ])
                        ]
                    ])
                ],
                'expectedData' => Price::create(1, 'USD'),
            ],
        ];
    }

    public function testGetPriceCache(): void
    {
        $methodId = 'flat_rate';
        $typeId = 'primary';

        $shippingLineItems = [new ShippingLineItem([])];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $ruleId = 222;
        $this->shippingRulesProvider->expects(self::exactly(2))
            ->method('getShippingMethodsConfigsRules')
            ->with($context)
            ->willReturn([
                $this->getEntity(ShippingMethodsConfigsRule::class, [
                    'id' => $ruleId,
                    'methodConfigs' => [
                        $this->getEntity(ShippingMethodConfig::class, [
                            'method' => $methodId,
                            'typeConfigs' => [
                                $this->getEntity(ShippingMethodTypeConfig::class, [
                                    'enabled' => true,
                                    'type' => $typeId,
                                    'options' => [
                                        'price' => Price::create(1, 'USD'),
                                    ],
                                ])
                            ],
                        ])
                    ]
                ])
            ]);

        $expectedPrice = Price::create(1, 'USD');

        $this->priceCache->expects(self::exactly(2))
            ->method('hasRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturnOnConsecutiveCalls(false, true);
        $this->priceCache->expects(self::once())
            ->method('saveRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId, Price::create(1, 'USD'))
            ->willReturn(true);
        $this->priceCache->expects(self::once())
            ->method('getRulePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturn(Price::create(2, 'USD'));

        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
        $expectedPrice->setValue(2);
        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
    }

    public function testGetPriceWhenNoMethodId(): void
    {
        $shippingLineItems = [new ShippingLineItem([])];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->shippingRulesProvider->expects($this->never())
            ->method('getShippingMethodsConfigsRules');

        $this->priceCache->expects($this->never())
            ->method('saveRulePrice');

        $this->assertNull($this->shippingPriceProvider->getPrice($context, null, 'ground'));
    }
}
