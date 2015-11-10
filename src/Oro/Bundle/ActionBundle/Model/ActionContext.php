<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionContext extends AbstractStorage implements EntityAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->offsetGet('data');
    }
}
