<?php

namespace Oro\Component\Tree\Tests\Unit\Stubs\Handler;

use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Tests\Unit\Stubs\EntityStub;

class TreeHandlerStub extends AbstractTreeHandler
{
    #[\Override]
    protected function moveProcessing($entityId, $parentId, $position)
    {
    }

    /**
     *
     * @param EntityStub $entity
     */
    #[\Override]
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->id,
            'parent' => $entity->parent,
            'text'   => $entity->text,
        ];
    }
}
