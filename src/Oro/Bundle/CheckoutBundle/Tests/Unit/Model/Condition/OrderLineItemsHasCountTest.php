<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Model\Condition\OrderLineItemsHasCount;

class OrderLineItemsHasCountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderLineItemsHasCount
     */
    protected $condition;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    protected function setUp(): void
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()->getMock();

        $this->condition = new OrderLineItemsHasCount($this->manager);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemsHasCount::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider initializeDataProvider
     * @param array $options
     * @param $message
     */
    public function testInitializeExceptions(array $options, $message)
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            [
                'options' => [1, 2, 3],
                'exceptionMessage' => 'Options must have 1 elements, but 3 given.',
            ],
            [
                'options' => [],
                'exceptionMessage' => 'Options must have 1 elements, but 0 given.',
            ],
            [
                'options' => [1 => 1],
                'exceptionMessage' => 'Option "entity" must be set.',
            ]
        ];
    }

    public function testEvaluateException()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must implement Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface');

        $context = [];
        $this->condition->initialize(['entity' => []]);
        $this->condition->evaluate($context);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param Collection $lineItems
     * @param bool $rfpVisibility
     * @param bool $expectedResult
     * @param string $expectedMessage
     */
    public function testEvaluate(Collection $lineItems, $rfpVisibility, $expectedResult, $expectedMessage)
    {
        /** @var CheckoutInterface|\PHPUnit\Framework\MockObject\MockObject $checkout */
        $checkout = $this->createMock('Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface');
        $context = [];
        $this->condition->initialize(['entity' => $checkout]);
        $this->manager->expects($this->at(0))->method('getData')->willReturn($lineItems);
        if (!$expectedResult) {
            $data = $rfpVisibility ? ['item'] : [];
            $this->manager->expects($this->at(1))->method('getData')->willReturn(new ArrayCollection($data));
        }
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
        $this->assertEquals($expectedMessage, $this->getMessage());
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            [
                'lineItems' => new ArrayCollection([
                    $this->createMock('Oro\Bundle\OrderBundle\Entity\OrderLineItem'),
                ]),
                'rfpVisibility' => true,
                'expectedResult' => true,
                'expectedMessage' => '',
            ],
            [
                'lineItems' => new ArrayCollection([]),
                'rfpVisibility' => true,
                'expectedResult' => false,
                'expectedMessage' => 'oro.checkout.workflow.condition.order_line_item_has_count_allow_rfp.message',
            ],
            [
                'lineItems' => new ArrayCollection([]),
                'rfpVisibility' => false,
                'expectedResult' => false,
                'expectedMessage' => 'oro.checkout.workflow.condition.order_line_item_has_count_not_allow_rfp.message',
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getMessage()
    {
        $reflectionMethod = new \ReflectionMethod(get_class($this->condition), 'getMessage');
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke($this->condition);
    }
}
