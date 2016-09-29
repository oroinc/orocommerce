<?php

namespace Oro\Bundle\OrderBundle\Doctrine\ORM\Id;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

class SimpleEntityAwareGenerator implements EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    public function generate($entity)
    {
        return UUIDGenerator::v4();
    }
}
