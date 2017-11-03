<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsMassActionArgsParser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class AddProductsMassActionArgsParserTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetProductIdsWhenAllProductsSelected()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData(
            [
                'inset' => 0,
                'values' => '',
                'shoppingList' => '1'
            ]
        ));

        $this->assertCount(0, $parser->getProductIds());
    }

    public function testGetProductIdsWhenProductsIdsProvided()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData(
            [
                'inset' => 1,
                'values' => '1,2',
                'shoppingList' => '1'
            ]
        ));

        $this->assertCount(2, $parser->getProductIds());
    }

    public function testGetCreatedShoppingListWhenNoCreatedShoppingListProvided()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData([]));

        $this->assertNull($parser->getCreatedShoppingList());
    }

    public function testGetCreatedShoppingListWhenCreatedShoppingListProvided()
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $parser = new AddProductsMassActionArgsParser(
            $this->createHandlerArgsWithData(['createdShoppingList' => $shoppingList])
        );

        $this->assertEquals($shoppingList, $parser->getCreatedShoppingList());
    }

    public function testGetShoppingListIdWhenNoShoppingListIdProvided()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData([]));

        $this->assertNull($parser->getShoppingListId());
    }

    public function testGetShoppingListIdWhenShoppingListIdProvided()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData(['shoppingList' => 5]));

        $this->assertEquals(5, $parser->getShoppingListId());
    }

    public function testGetUnitsAndQuantitiesWhenEmpty()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData([]));
        $this->assertEmpty($parser->getUnitsAndQuantities());
    }

    public function testGetUnitsAndQuantities()
    {
        $parser = new AddProductsMassActionArgsParser($this->createHandlerArgsWithData([
            'units_and_quantities' => '{"SKU2":{"set":2},"SKU3":{"item":4}}'
        ]));

        $expectedUnitsAndQuantities = ['SKU2' => ['set' => 2], 'SKU3' => ['item' => 4]];
        $this->assertEquals($expectedUnitsAndQuantities, $parser->getUnitsAndQuantities());
    }

    /**
     * @param array $data
     * @return MassActionHandlerArgs
     */
    private function createHandlerArgsWithData(array $data): MassActionHandlerArgs
    {
        /** @var MassActionInterface $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResultInterface $iterableResult */
        $iterableResult = $this->createMock(IterableResultInterface::class);

        return new MassActionHandlerArgs($massAction, $dataGrid, $iterableResult, $data);
    }
}
