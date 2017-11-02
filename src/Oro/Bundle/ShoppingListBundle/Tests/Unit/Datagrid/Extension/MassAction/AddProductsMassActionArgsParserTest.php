<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsMassActionArgsParser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class AddProductsMassActionArgsParserTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetProductIds()
    {
        $parser = new AddProductsMassActionArgsParser($this->getArgs(0, '', '1'));
        $this->assertCount(0, $parser->getProductIds());
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2', '1'));
        $this->assertCount(2, $parser->getProductIds());
    }

    public function testGetShoppingList()
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2', $shoppingList));
        $this->assertEquals($shoppingList, $parser->getShoppingList());
        $parser = new AddProductsMassActionArgsParser($this->getArgs(1, '1,2'));
        $this->assertNull($parser->getShoppingList());
    }

    public function testGetUnitsAndQuantitiesWhenEmpty()
    {
        /** @var MassActionHandlerArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(MassActionHandlerArgs::class);
        $args
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $parser = new AddProductsMassActionArgsParser($args);
        $this->assertEmpty($parser->getUnitsAndQuantities());
    }

    public function testGetUnitsAndQuantities()
    {
        /** @var MassActionHandlerArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(MassActionHandlerArgs::class);
        $args
            ->expects($this->once())
            ->method('getData')
            ->willReturn([
                'units_and_quantities' => '{"SKU2":{"set":2},"SKU3":{"item":4}}'
            ]);

        $expectedUnitsAndQuantities = ['SKU2' => ['set' => 2], 'SKU3' => ['item' => 4]];
        $parser = new AddProductsMassActionArgsParser($args);
        $this->assertEquals($expectedUnitsAndQuantities, $parser->getUnitsAndQuantities());
    }

    /**
     * @param int      $inset
     * @param string   $values
     * @param ShoppingList|null $shoppingList
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
