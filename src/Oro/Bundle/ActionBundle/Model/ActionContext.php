<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionContext extends AbstractStorage implements EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->offsetGet('data');
    }
}
