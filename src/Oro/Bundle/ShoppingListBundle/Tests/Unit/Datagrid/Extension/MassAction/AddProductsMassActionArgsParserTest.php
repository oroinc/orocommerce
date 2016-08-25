<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsMassActionArgsParser;

class AddProductsMassActionArgsParserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProductIds()
    {
        $parser = new AddProductsMassActionArgsParser($this->getArgs(0, '', '1'));
        $this->assertCount(0, $parser->getProductIds());
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2', '1'));
        $this->assertCount(2, $parser->getProductIds());
    }

    public function testGetShoppingListId()
    {
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2', '1'));
        $this->assertEquals(1, $parser->getShoppingListId());
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2', 'current'));
        $this->assertNull($parser->getShoppingListId());
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2'));
        $this->assertNull($parser->getShoppingListId());
    }

    /**
     * @param int      $inset
     * @param string   $values
     * @param int|null $shoppingList
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|MassActionHandlerArgs
     */
    protected function getArgs($inset, $values, $shoppingList = null)
    {
        $result = [
            'inset' => $inset,
            'values' => $values
        ];
        if ($shoppingList) {
            $result['shoppingList'] = $shoppingList;
        }

        $args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getData')
            ->willReturn($result);

        return $args;
    }
}
