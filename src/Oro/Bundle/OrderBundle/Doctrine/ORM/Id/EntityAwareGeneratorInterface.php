<?php

namespace Oro\Bundle\OrderBundle\Doctrine\ORM\Id;

interface EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    public function generate($entity);
}
