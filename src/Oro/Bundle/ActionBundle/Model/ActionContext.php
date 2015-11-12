<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionContext extends AbstractStorage implements EntityAwareInterface
{
    /**
     * @return Object
     */
    public function getEntity()
    {
        return $this->offsetGet('entity');
    }
}
