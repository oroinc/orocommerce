<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\ORM\Query\ResultIterator\SqlWalkerHelperTrait
    as AbstractSqlWalkerHelperTrait;

trait SqlWalkerHelperTrait
{
    use AbstractSqlWalkerHelperTrait;

    /**
     * {@inheritdoc}
     */
    protected function getClassName(): string
    {
        return InventoryLevel::class;
    }
}
