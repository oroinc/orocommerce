<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

class WarehouseSelectTypeStub extends StubEntityType
{
    use EntityTrait;

    const WAREHOUSE_1 = 1;
    const WAREHOUSE_2 = 2;
    const WAREHOUSE_3 = 3;

    public function __construct()
    {
        parent::__construct(
            [
                self::WAREHOUSE_1 => $this->getEntity(Warehouse::class, ['id' => self::WAREHOUSE_1]),
                self::WAREHOUSE_2 => $this->getEntity(Warehouse::class, ['id' => self::WAREHOUSE_2]),
                self::WAREHOUSE_3 => $this->getEntity(Warehouse::class, ['id' => self::WAREHOUSE_3]),
            ],
            WarehouseSelectType::NAME
        );
    }
}
