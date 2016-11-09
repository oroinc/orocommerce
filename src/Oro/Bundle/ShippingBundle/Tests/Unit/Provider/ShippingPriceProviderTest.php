<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\PriceAwareShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingRulesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingRulesProvider;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCache;

    /**
     * @var ShippingPriceProvider
     */
    protected $shippingPriceProvider;

    protected function setUp()
    {
        $this->shippingRulesProvider = $this->getMockBuilder(ShippingRulesProvider::class)
            ->disableOriginalConstructor()->getMock();

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

        $this->registry = $this->getMock(ShippingMethodRegistry::class);
        $this->registry->expects($this->any())
            ->method('getShippingMethod')
            ->will($this->returnCallback(function ($methodId) use ($methods) {
                return array_key_exists($methodId, $methods) ? $methods[$methodId] : null;
            }));

        $this->priceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()->getMock();

        $this->shippingPriceProvider = new ShippingPriceProvider(
            $this->shippingRulesProvider,
            $this->registry,
            $this->priceCache
        );
    }

    /**
     * @dataProvider getApplicableShippingRulesProvider
     *
     * @param array $shippingRules
     * @param array $expectedData
     */
    public function testGetApplicableMethodsWithTypesData(array $shippingRules, array $expectedData)
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD',
            'lineItems' => [$this->getEntity(ShippingLineItem::class)]
        ]);
        $this->shippingRulesProvider->expects($this->once())
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn($shippingRules);

        $this->assertEquals($expectedData, $this->shippingPriceProvider->getApplicableMethodsWithTypesData($context));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getApplicableShippingRulesProvider()
    {
        return [
            'one rule' => [
                'shippingRule' => [
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
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
                                'price' => Price::create(12, 'USD'),
                                'options' => ['price' => Price::create(12, 'USD')],
                                'methodOptions' => [],
                            ]
                        ]
                    ]
                ]
            ],
            'several rules with same methods ans diff types' => [
                'shippingRule' => [
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'aware_price' => Price::create(3, 'USD'),
                                        ],
                                    ]),
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'air',
                                        'options' => [
                                            'aware_price' => Price::create(4, 'USD'),
                                        ],
                                    ]),
                                ],
                            ]),
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                                'options' => ['price' => Price::create(1, 'USD')],
                                'methodOptions' => [],
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
                                'options' => ['aware_price' => Price::create(2, 'USD')],
                                'methodOptions' => [],
                            ],
                            'air' => [
                                'identifier' => 'air',
                                'label' => 'air.label',
                                'sortOrder' => 2,
                                'price' => Price::create(4, 'USD'),
                                'options' => ['aware_price' => Price::create(4, 'USD')],
                                'methodOptions' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testGetApplicableMethodsWithTypesDataCache()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD',
            'lineItems' => [$this->getEntity(ShippingLineItem::class)]
        ]);
        $this->shippingRulesProvider->expects(static::exactly(2))
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn([
                $this->getEntity(ShippingRule::class, [
                    'methodConfigs' => [
                        $this->getEntity(ShippingRuleMethodConfig::class, [
                            'method' => 'flat_rate',
                            'typeConfigs' => [
                                $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                        'price' => $price,
                        'options' => ['price' => Price::create(1, 'USD')],
                        'methodOptions' => [],
                    ]
                ]
            ]
        ];

        $this->priceCache->expects(static::at(0))
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(false);

        $this->priceCache->expects(static::at(1))
            ->method('savePrice')
            ->with($context, 'flat_rate', 'primary', Price::create(1, 'USD'))
            ->willReturn(true);

        $this->priceCache->expects(static::at(2))
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(true);

        $this->priceCache->expects(static::at(3))
            ->method('getPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(Price::create(2, 'USD'));

        $this->assertEquals($expectedData, $this->shippingPriceProvider->getApplicableMethodsWithTypesData($context));
        $price->setValue(2);
        $this->assertEquals($expectedData, $this->shippingPriceProvider->getApplicableMethodsWithTypesData($context));
    }

    /**
     * @dataProvider getPriceDataProvider
     *
     * @param string $methodId
     * @param string $typeId
     * @param array $shippingRules
     * @param Price|null $expectedPrice
     */
    public function testGetPrice($methodId, $typeId, array $shippingRules, Price $expectedPrice = null)
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD',
            'lineItems' => [$this->getEntity(ShippingLineItem::class)]
        ]);
        $this->shippingRulesProvider->expects($this->once())
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn($shippingRules);

        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getPriceDataProvider()
    {
        return [
            'no rule' => [
                'methodId' => 'integration_method',
                'typeId' => 'ground',
                'shippingRules' => [
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'flat_rate',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
            'several rules with same methods ans types' => [
                'methodId' => 'integration_method',
                'typeId' => 'ground',
                'shippingRules' => [
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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
                    $this->getEntity(ShippingRule::class, [
                        'methodConfigs' => [
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
                                        'enabled' => true,
                                        'type' => 'ground',
                                        'options' => [
                                            'price' => Price::create(2, 'USD'),
                                        ],
                                    ])
                                ],
                            ]),
                            $this->getEntity(ShippingRuleMethodConfig::class, [
                                'method' => 'integration_method',
                                'typeConfigs' => [
                                    $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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

    public function testGetPriceCache()
    {
        $methodId = 'flat_rate';
        $typeId = 'primary';
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD',
            'lineItems' => [$this->getEntity(ShippingLineItem::class)]
        ]);
        $this->shippingRulesProvider->expects(static::exactly(2))
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn([
                $this->getEntity(ShippingRule::class, [
                    'methodConfigs' => [
                        $this->getEntity(ShippingRuleMethodConfig::class, [
                            'method' => $methodId,
                            'typeConfigs' => [
                                $this->getEntity(ShippingRuleMethodTypeConfig::class, [
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

        $this->priceCache->expects(static::at(0))
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(false);

        $this->priceCache->expects(static::at(1))
            ->method('savePrice')
            ->with($context, 'flat_rate', 'primary', Price::create(1, 'USD'))
            ->willReturn(true);

        $this->priceCache->expects(static::at(2))
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(true);

        $this->priceCache->expects(static::at(3))
            ->method('getPrice')
            ->with($context, 'flat_rate', 'primary')
            ->willReturn(Price::create(2, 'USD'));

        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
        $expectedPrice->setValue(2);
        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
    }

    public function testGetPriceNoMethodAndType()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD',
            'lineItems' => [$this->getEntity(ShippingLineItem::class)]
        ]);
        $this->assertNull($this->shippingPriceProvider->getPrice($context, 'unknown_method', 'primary'));
        $this->assertNull($this->shippingPriceProvider->getPrice($context, 'flat_rate', 'unknown_method'));
    }
}
