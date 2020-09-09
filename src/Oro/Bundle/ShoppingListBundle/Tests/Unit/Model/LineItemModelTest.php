<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

class LineItemModelTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $id = 42;
        $qty = 5.55;
        $unitCode = 'item';

        $model = new LineItemModel($id, $qty, $unitCode);

        $this->assertEquals($id, $model->getId());
        $this->assertEquals($qty, $model->getQuantity());
        $this->assertEquals($unitCode, $model->getUnitCode());
    }
}
