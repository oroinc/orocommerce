<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;
use Symfony\Component\HttpFoundation\Request;

class QuickAddProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListLineItemHandler */
    protected $handler;

    /** @var QuickAddProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->handler = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new QuickAddProcessor($this->handler);
    }

    protected function tearDown()
    {
        unset($this->handler, $this->processor);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->processor->getName());
        $this->assertEquals(QuickAddProcessor::NAME, $this->processor->getName());
    }

    /**
     * @param array $data
     * @param mixed $shoppingList
     * @param bool $expects
     * @dataProvider processDataProvider
     */
    public function testProcess(array $data, $shoppingList = null, $expects = false)
    {
        $this->handler->expects($this->any())->method('getShoppingList')->willReturn($shoppingList);
        $this->handler->expects($expects ? $this->once() : $this->never())
            ->method('createForShoppingList')
            ->with(
                $shoppingList,
                $this->callback(
                    function (array $productIds) use ($data) {
                        $this->assertArrayHasKey('productIds', $data);
                        $this->assertEquals($data['productIds'], $productIds);

                        return true;
                    }
                )
            );

        $this->processor->process($data, new Request());
    }

    /** @return array */
    public function processDataProvider()
    {
        return [
            'empty' => [[]],
            'shopping list' => [['shoppingList' => 1]],
            'product ids' => [['productIds' => [1]]],
            'invalid data' => [['productIds' => [], 'shoppingList' => 0]],
            'invalid data 2' => [['productIds' => [], 'shoppingList' => 'a']],
            'invalid data 3' => [['productIds' => 'a', 'shoppingList' => 1]],
            'valid data shopping list not found' => [['productIds' => [1], 'shoppingList' => '1']],
            'valid data' => [['productIds' => [1], 'shoppingList' => '1'], new ShoppingList(), true],
        ];
    }
}
