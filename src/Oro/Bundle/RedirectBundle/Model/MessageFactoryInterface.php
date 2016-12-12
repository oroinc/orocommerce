<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

interface MessageFactoryInterface
{
    /**
     * @param SluggableInterface|object $entity
     */
    public function createMessage(SluggableInterface $entity);

    /**
     * @param array $data
     * @return object|SluggableInterface
     */
    public function getEntityFromMessage($data);

    /**
     * @param array $data
     * @return string
     */
    public function getEntityClassFromMessage($data);
}
