<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\BasicShippingMethodChoicesProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Symfony\Component\Translation\TranslatorInterface;

class BasicShippingMethodChoicesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject phpdoc
     */
    protected $registry;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject phpdoc
     */
    protected $translator;

    /**
     * @var BasicShippingMethodChoicesProvider
     */
    protected $choicesProvider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ShippingMethodRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->choicesProvider = new BasicShippingMethodChoicesProvider($this->registry, $this->translator);
    }

    /**
     * @param array $methods
     * @param array $result
     * @param bool  $translate
     *
     * @dataProvider methodsProvider
     */
    public function testGetMethods($methods, $result, $translate = false)
    {
        $translation = [
            ['flat rate', [], null, null, 'flat rate translated'],
            ['ups', [], null, null, 'ups translated'],
        ];

        $this->registry->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn($methods);

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValueMap($translation));

        $this->assertEquals($result, $this->choicesProvider->getMethods($translate));
    }

    /**
     * @return array
     */
    public function methodsProvider()
    {
        return
            [
                'some_methods' =>
                    [
                        'methods' =>
                            [
                                'flat_rate' => $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'flat_rate',
                                        'sortOrder' => 1,
                                        'label' => 'flat rate',
                                        'isEnabled' => true,
                                        'types' => [],
                                    ]
                                ),
                                'ups' => $this->getEntity(
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
                        'result' => ['flat_rate' => 'flat rate', 'ups' => 'ups'],
                        'translate' => false,
                    ],
                'some_methods_with_translation' =>
                    [
                        'methods' =>
                            [
                                'flat_rate' => $this->getEntity(
                                    ShippingMethodStub::class,
                                    [
                                        'identifier' => 'flat_rate',
                                        'sortOrder' => 1,
                                        'label' => 'flat rate',
                                        'isEnabled' => true,
                                        'types' => [],
                                    ]
                                ),
                                'ups' => $this->getEntity(
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
                        'result' => ['flat_rate' => 'flat rate translated', 'ups' => 'ups translated'],
                        'translate' => true,
                    ],
                'no_methods' =>
                    [
                        'methods' => [],
                        'result' => [],
                    ],
            ];
    }
}
