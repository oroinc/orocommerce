<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingRulesProvider;

    /** @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $priceCache;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var ShippingPriceProvider */
    private $shippingPriceProvider;

    protected function setUp(): void
    {
        $this->shippingRulesProvider = $this->createMock(MethodsConfigsRulesByContextProviderInterface::class);
        $this->priceCache = $this->createMock(ShippingPriceCache::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $methods = [
            'flat_rate' => $this->getShippingMethod('flat_rate', 1, [
                'primary' => $this->getShippingMethodType('primary', 1)
            ]),
            'integration_method' => $this->getPriceAwareShippingMethod('integration_method', 2, true, [
                'ground' => $this->getShippingMethodType('ground', 1),
                'air' => $this->getShippingMethodType('air', 2)
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
            $this->eventDispatcher,
            $this->memoryCacheProvider
        );
    }

    private function getShippingMethod(string $identifier, int $sortOrder, array $types): ShippingMethodStub
    {
        $shippingMethod = new ShippingMethodStub();
        $shippingMethod->setIdentifier($identifier);
        $shippingMethod->setSortOrder($sortOrder);
        $shippingMethod->setTypes($types);

        return $shippingMethod;
    }

    private function getPriceAwareShippingMethod(
        string $identifier,
        int $sortOrder,
        bool $isGrouped,
        array $types
    ): PriceAwareShippingMethodStub {
        $shippingMethod = new PriceAwareShippingMethodStub();
        $shippingMethod->setIdentifier($identifier);
        $shippingMethod->setSortOrder($sortOrder);
        $shippingMethod->setIsGrouped($isGrouped);
        $shippingMethod->setTypes($types);

        return $shippingMethod;
    }

    private function getShippingMethodType(string $identifier, int $sortOrder): ShippingMethodTypeStub
    {
        $shippingMethodType = new ShippingMethodTypeStub();
        $shippingMethodType->setIdentifier($identifier);
        $shippingMethodType->setSortOrder($sortOrder);

        return $shippingMethodType;
    }

    private function getShippingMethodsConfigsRule(int $id, array $methodConfigs): ShippingMethodsConfigsRule
    {
        $shippingMethodsConfigsRule = new ShippingMethodsConfigsRule();
        ReflectionUtil::setId($shippingMethodsConfigsRule, $id);
        foreach ($methodConfigs as $methodConfig) {
            $shippingMethodsConfigsRule->addMethodConfig($methodConfig);
        }

        return $shippingMethodsConfigsRule;
    }

    private function getShippingMethodConfig(string $method, array $typeConfigs = []): ShippingMethodConfig
    {
        $shippingMethodConfig = new ShippingMethodConfig();
        $shippingMethodConfig->setMethod($method);
        foreach ($typeConfigs as $typeConfig) {
            $shippingMethodConfig->addTypeConfig($typeConfig);
        }

        return $shippingMethodConfig;
    }

    private function getShippingMethodTypeConfig(string $type, array $options): ShippingMethodTypeConfig
    {
        $shippingMethodTypeConfig = new ShippingMethodTypeConfig();
        $shippingMethodTypeConfig->setEnabled(true);
        $shippingMethodTypeConfig->setType($type);
        $shippingMethodTypeConfig->setOptions($options);

        return $shippingMethodTypeConfig;
    }

    public function testGetApplicablePaymentMethodsWhenCache(): void
    {
        $methodViews = $this->createMock(ShippingMethodViewCollection::class);
        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use ($methodViews) {
                return $methodViews;
            });

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

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->assertEquals(
            $expectedData,
            $this->shippingPriceProvider->getApplicableMethodsViews($context)->toArray()
        );
    }

    /**
     * @dataProvider getApplicableShippingMethodsConfigsRulesProvider
     */
    public function testGetApplicableMethodsViewsWhenMemoryCacheProvider(array $shippingRules, array $expectedData)
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

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->assertEquals(
            $expectedData,
            $this->shippingPriceProvider->getApplicableMethodsViews($context)->toArray()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getApplicableShippingMethodsConfigsRulesProvider(): array
    {
        return [
            'one rule' => [
                'shippingRule' => [
                    $this->getShippingMethodsConfigsRule(111, [
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(12, 'USD')])
                        ])
                    ]),
                    $this->getShippingMethodsConfigsRule(222, [
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('ground', ['aware_price' => null])
                        ])
                    ]),
                    $this->getShippingMethodsConfigsRule(333, [
                        $this->getShippingMethodConfig('unknown_method')
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
                    $this->getShippingMethodsConfigsRule(1234, [
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('ground', ['aware_price' => Price::create(2, 'USD')])
                        ])
                    ]),
                    $this->getShippingMethodsConfigsRule(2345, [
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(1, 'USD')])
                        ])
                    ]),
                    $this->getShippingMethodsConfigsRule(4567, [
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('ground', ['aware_price' => Price::create(3, 'USD')]),
                            $this->getShippingMethodTypeConfig('air', ['aware_price' => Price::create(4, 'USD')]),
                        ]),
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(5, 'USD')])
                        ])
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
            ShippingContext::FIELD_CURRENCY => 'USD',
            ShippingContext::FIELD_SOURCE_ENTITY => new \stdClass()
        ]);

        $ruleId = 111;
        $this->shippingRulesProvider->expects(self::exactly(2))
            ->method('getShippingMethodsConfigsRules')
            ->with($context)
            ->willReturn([
                $this->getShippingMethodsConfigsRule($ruleId, [
                    $this->getShippingMethodConfig('flat_rate', [
                        $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(1, 'USD')])
                    ])
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
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturnOnConsecutiveCalls(false, true);
        $this->priceCache->expects(self::once())
            ->method('savePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId, Price::create(1, 'USD'));
        $this->priceCache->expects(self::once())
            ->method('getPrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturn(Price::create(2, 'USD'));

        $this->memoryCacheProvider->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

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
    public function testGetPrice(?string $methodId, string $typeId, array $shippingRules, Price $expectedPrice = null)
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
            ->method('savePrice');

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
                    $this->getShippingMethodsConfigsRule(111, [
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(12, 'USD')])
                        ])
                    ])
                ],
                'expectedData' => null,
            ],
            'one rule' => [
                'methodId' => 'flat_rate',
                'typeId' => 'primary',
                'shippingRules' => [
                    $this->getShippingMethodsConfigsRule(222, [
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => Price::create(12, 'USD')])
                        ])
                    ])
                ],
                'expectedData' => Price::create(12, 'USD'),
            ],
            'no price' => [
                'methodId' => 'flat_rate',
                'typeId' => 'primary',
                'shippingRules' => [
                    $this->getShippingMethodsConfigsRule(333, [
                        $this->getShippingMethodConfig('flat_rate', [
                            $this->getShippingMethodTypeConfig('primary', ['price' => null])
                        ])
                    ])
                ],
                'expectedData' => null,
            ],
            'several rules with same methods ans types' => [
                'methodId' => 'integration_method',
                'typeId' => 'ground',
                'shippingRules' => [
                    $this->getShippingMethodsConfigsRule(444, [
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('ground', ['price' => Price::create(1, 'USD')])
                        ])
                    ]),
                    $this->getShippingMethodsConfigsRule(1234, [
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('ground', ['price' => Price::create(2, 'USD')])
                        ]),
                        $this->getShippingMethodConfig('integration_method', [
                            $this->getShippingMethodTypeConfig('air', ['price' => Price::create(3, 'USD')])
                        ])
                    ])
                ],
                'expectedData' => Price::create(1, 'USD'),
            ],
        ];
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
            ->method('savePrice');

        $this->assertNull($this->shippingPriceProvider->getPrice($context, null, 'ground'));
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
                $this->getShippingMethodsConfigsRule($ruleId, [
                    $this->getShippingMethodConfig($methodId, [
                        $this->getShippingMethodTypeConfig($typeId, ['price' => Price::create(1, 'USD')])
                    ])
                ])
            ]);

        $expectedPrice = Price::create(1, 'USD');

        $this->priceCache->expects(self::exactly(2))
            ->method('hasPrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturnOnConsecutiveCalls(false, true);
        $this->priceCache->expects(self::once())
            ->method('savePrice')
            ->with($context, 'flat_rate', 'primary', $ruleId, Price::create(1, 'USD'));
        $this->priceCache->expects(self::once())
            ->method('getPrice')
            ->with($context, 'flat_rate', 'primary', $ruleId)
            ->willReturn(Price::create(2, 'USD'));

        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
        $expectedPrice->setValue(2);
        $this->assertEquals($expectedPrice, $this->shippingPriceProvider->getPrice($context, $methodId, $typeId));
    }

    public function testGetPriceNoMethodAndType(): void
    {
        $shippingLineItems = [new ShippingLineItem([])];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->assertNull($this->shippingPriceProvider->getPrice($context, 'unknown_method', 'primary'));
        $this->assertNull($this->shippingPriceProvider->getPrice($context, 'flat_rate', 'unknown_method'));
    }
}
