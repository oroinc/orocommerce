<?php

namespace Oro\Bundle\OrderBundle\Doctrine\ORM\Id;

class SimpleEntityAwareGenerator implements EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    #[\Override]
    public function generate($entity)
    {
        return $entity->getId();
    }
}
