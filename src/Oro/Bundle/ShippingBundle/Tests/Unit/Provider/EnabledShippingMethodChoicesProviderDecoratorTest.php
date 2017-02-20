<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\BasicShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\EnabledShippingMethodChoicesProviderDecorator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;

class EnabledShippingMethodChoicesProviderDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @var BasicShippingMethodChoicesProvider
     */
    protected $choicesProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface
     */
    protected $enabledChoicesProvider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ShippingMethodRegistry::class);
        $this->choicesProvider = $this->createMock(BasicShippingMethodChoicesProvider::class);
        $this->enabledChoicesProvider = new EnabledShippingMethodChoicesProviderDecorator(
            $this->registry,
            $this->choicesProvider
        );
    }

    /**
     * @param array  $registryMap
     * @param array  $choices
     * @param array  $result
     * @dataProvider methodsProvider
     */
    public function testGetMethods($registryMap, $choices, $result)
    {
        $this->registry->expects($this->any())
            ->method('getShippingMethod')
            ->will($this->returnValueMap($registryMap));

        $this->choicesProvider->expects($this->once())
            ->method('getMethods')
            ->willReturn($choices);

        $this->assertEquals($this->enabledChoicesProvider->getMethods(), $result);

    }
    /**
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
                                ['flat_rate', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'flat_rate',
                                        'sortOrder' => 1,
                                        'label' => 'flat rate',
                                        'isEnabled' => true,
                                        'types' => []
                                    ]
                                )],
                                ['ups', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'ups',
                                        'sortOrder' => 1,
                                        'label' => 'ups',
                                        'isEnabled' => true,
                                        'types' => []
                                    ]
                                ),
                                ]
                            ],
                        'choices' => ['flat_rate' => 'flat rate', 'ups' => 'ups'],
                        'result' => ['flat_rate' => 'flat rate', 'ups' => 'ups']
                    ],
                'some_methods_disabled' =>
                    [
                        'methods_map' =>
                            [
                                ['flat_rate', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'flat_rate',
                                        'sortOrder' => 1,
                                        'label' => 'flat rate',
                                        'isEnabled' => true,
                                        'types' => []
                                    ]
                                )],
                                ['ups', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'ups',
                                        'sortOrder' => 1,
                                        'label' => 'ups',
                                        'isEnabled' => false,
                                        'types' => []
                                    ]
                                ),
                                ]
                            ],
                        'choices' => ['flat_rate' => 'flat rate', 'ups' => 'ups'],
                        'result' => ['flat_rate' => 'flat rate']
                    ],
                'some_methods_disabled' =>
                    [
                        'methods_map' =>
                        [
                            ['flat_rate', $this->getEntity(
                                ShippingMethodStub::class,
                                [
                                    'identifier' => 'flat_rate',
                                    'sortOrder' => 1,
                                    'label' => 'flat rate',
                                    'isEnabled' => true,
                                    'types' => []
                                ]
                            )],
                                ['ups', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'ups',
                                        'sortOrder' => 1,
                                        'label' => 'ups',
                                        'isEnabled' => false,
                                        'types' => []
                                    ]
                                ),
                                ]
                            ],
                        'choices' => ['flat_rate' => 'flat rate', 'ups' => 'ups'],
                        'result' => ['flat_rate' => 'flat rate']
                    ],
                'all_disabled_methods' =>
                    [
                        'methods_map' =>
                            [
                                ['flat_rate', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'flat_rate',
                                        'sortOrder' => 1,
                                        'label' => 'flat rate',
                                        'isEnabled' => false,
                                        'types' => []
                                    ]
                                )],
                                ['ups', $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'ups',
                                        'sortOrder' => 1,
                                        'label' => 'ups',
                                        'isEnabled' => false,
                                        'types' => []
                                    ]
                                ),
                                ]
                            ],
                        'choices' => ['flat_rate' => 'flat rate', 'ups' => 'ups'],
                        'result' => []
                    ],
                'no_methods' =>
                    [
                        'methods' => [],
                        'choices' => [],
                        'result' => []
                    ]
            ];
    }
}
