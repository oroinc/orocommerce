<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionArgsParser as ArgsParser;

class AddProductsMassActionHandlerArgsParserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProductIds()
    {
        $parser = new ArgsParser($this->getArgs(0, '', '1'));
        $this->assertCount(0, $parser->getProductIds());
        $parser = new ArgsParser($this->getArgs(1, '1,2', '1'));
        $this->assertCount(2, $parser->getProductIds());
    }

    public function testGetShoppingListId()
    {
        $parser = new ArgsParser($this->getArgs(1, '1,2', '1'));
        $this->assertEquals(1, $parser->getShoppingListId());
        $parser = new ArgsParser($this->getArgs(1, '1,2', 'current'));
        $this->assertNull($parser->getShoppingListId());
    }

    /**
     * @param int    $inset
     * @param string $values
     * @param int    $shoppingList
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|MassActionHandlerArgs
     */
    protected function getArgs($inset, $values, $shoppingList)
    {
        $args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getData')
            ->willReturn([
                'inset' => $inset,
                'values' => $values,
                'shoppingList' => $shoppingList
            ]);

        return $args;
    }
}
