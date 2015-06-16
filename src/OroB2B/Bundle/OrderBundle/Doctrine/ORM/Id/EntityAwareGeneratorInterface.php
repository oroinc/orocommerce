<?php
namespace OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id;

interface EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    public function generate($entity);
}
