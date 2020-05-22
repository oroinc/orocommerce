<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\EnabledShippingMethodChoicesProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Component\Testing\Unit\EntityTrait;

class EnabledShippingMethodChoicesProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject phpdoc
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface|\PHPUnit\Framework\MockObject\MockObject phpdoc
     */
    protected $choicesProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface
     */
    protected $enabledChoicesProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->choicesProvider = $this->createMock(ShippingMethodChoicesProviderInterface::class);
        $this->enabledChoicesProvider = new EnabledShippingMethodChoicesProviderDecorator(
            $this->shippingMethodProvider,
            $this->choicesProvider
        );
    }

    /**
     * @param array $registryMap
     * @param array $choices
     * @param array $result
     *
     * @dataProvider methodsProvider
     */
    public function testGetMethods($registryMap, $choices, $result)
    {
        $this->shippingMethodProvider->expects($this->any())
            ->method('getShippingMethod')
            ->will($this->returnValueMap($registryMap));

        $this->choicesProvider->expects($this->once())
            ->method('getMethods')
            ->willReturn($choices);

        $this->assertEquals($this->enabledChoicesProvider->getMethods(), $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function methodsProvider()
    {
        return
            [
                'all_methods_enabled' =>
                    [
                        'methods_map' =>
                            [
                                [
                                    'flat_rate',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'flat_rate',
                                            'sortOrder' => 1,
                                            'label' => 'flat rate',
                                            'isEnabled' => true,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                                [
                                    'ups',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'ups',
                                            'sortOrder' => 1,
                                            'label' => 'ups',
                                            'isEnabled' => true,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                            ],
                        'choices' => ['flat rate' => 'flat_rate', 'ups' => 'ups'],
                        'result' => ['flat rate' => 'flat_rate', 'ups' => 'ups'],
                    ],
                'some_methods_disabled' =>
                    [
                        'methods_map' =>
                            [
                                [
                                    'flat_rate',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'flat_rate',
                                            'sortOrder' => 1,
                                            'label' => 'flat rate',
                                            'isEnabled' => true,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                                [
                                    'ups',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'ups',
                                            'sortOrder' => 1,
                                            'label' => 'ups',
                                            'isEnabled' => false,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                            ],
                        'choices' => ['flat rate' => 'flat_rate', 'ups' => 'ups'],
                        'result' => ['flat rate' => 'flat_rate',],
                    ],
                'all_disabled_methods' =>
                    [
                        'methods_map' =>
                            [
                                [
                                    'flat_rate',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'flat_rate',
                                            'sortOrder' => 1,
                                            'label' => 'flat rate',
                                            'isEnabled' => false,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                                [
                                    'ups',
                                    $this->getEntity(
                                        ShippingMethodStub::class,
                                        [
                                            'identifier' => 'ups',
                                            'sortOrder' => 1,
                                            'label' => 'ups',
                                            'isEnabled' => false,
                                            'types' => [],
                                        ]
                                    ),
                                ],
                            ],
                        'choices' => ['flat rate' => 'flat_rate', 'ups' => 'ups'],
                        'result' => [],
                    ],
                'no_methods' =>
                    [
                        'methods' => [],
                        'choices' => [],
                        'result' => [],
                    ],
            ];
    }
}
