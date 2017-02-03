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
     * @param string $entityClass
     * @param array|int $id
     * @return array
     */
    public function createMassMessage($entityClass, $id);

    /**
     * @param array $data
     * @return array|SluggableInterface[]
     */
    public function getEntitiesFromMessage($data);

    /**
     * @param array $data
     * @return string
     */
    public function getEntityClassFromMessage($data);

    /**
     * @param array $data
     * @return bool
     */
    public function getCreateRedirectFromMessage($data);
}
