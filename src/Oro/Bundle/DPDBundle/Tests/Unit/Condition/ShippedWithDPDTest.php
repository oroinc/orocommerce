<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DPDBundle\Condition\ShippedWithDPD;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class ShippedWithDPDTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippedWithDPD */
    protected $condition;

    protected function setUp()
    {
        /**
         * @var DPDShippingMethodProvider
         */
        $dpdShippingMethodProvider = $this->getMockBuilder('Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider')
            ->disableOriginalConstructor()
            ->setMethods(['hasShippingMethod'])
            ->getMock();

        $dpdShippingMethodProviderMap = [
            ['dpd', true],
            ['no_dpd', false],
        ];

        $dpdShippingMethodProvider->expects($this->any())
            ->method('hasShippingMethod')
            ->will($this->returnValueMap($dpdShippingMethodProviderMap));

        $this->condition = new ShippedWithDPD($dpdShippingMethodProvider);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals(ShippedWithDPD::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'dpd_shipping_method'    => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'dpd'],
                'expectedResult' => true,
            ],
            'no_dpd_shipping_method' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'no_dpd'],
                'expectedResult' => false,
            ],
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => 'no_dpd'];
        $options = [new PropertyPath('foo')];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $message, 'parameters' => ['{{ value }}' => 'no_dpd']],
            $errors->get(0)
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function toArrayDataProvider()
    {
        return [
            [
                'options'  => ['value'],
                'message'  => null,
                'expected' => [
                    '@shipped_with_dpd' => [
                        'parameters' => [
                            'value',
                        ],
                    ],
                ],
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => [
                    '@shipped_with_dpd' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'value',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function compileDataProvider()
    {
        return [
            [
                'options'  => ['value'],
                'message'  => null,
                'expected' => '$factory->create(\'shipped_with_dpd\', [\'value\'])',
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'shipped_with_dpd\', [\'value\'])->setMessage(\'Test\')',
            ],
            [
                'options'  => [new PropertyPath('foo[bar].baz')],
                'message'  => null,
                'expected' => '$factory->create(\'shipped_with_dpd\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'foo[bar].baz\', [\'foo\', \'bar\', \'baz\'], [false, true, false])'
                    . '])',
            ],
        ];
    }
}
