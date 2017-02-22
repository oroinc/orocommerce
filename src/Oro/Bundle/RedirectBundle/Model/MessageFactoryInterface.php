<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

interface MessageFactoryInterface
{
    /**
     * @param SluggableInterface|object $entity
     * @return array
     */
    public function createMessage(SluggableInterface $entity);

    /**
     * @param string $entityClass
     * @param array|int $id
     * @param bool $createRedirect
     * @return array
     */
    public function createMassMessage($entityClass, $id, $createRedirect = true);

    /**
     * @param array|string $data
     * @return array|SluggableInterface[]
     */
    public function getEntitiesFromMessage($data);

    /**
     * @param array|string $data
     * @return string
     */
    public function getEntityClassFromMessage($data);

    /**
     * @param array|string $data
     * @return bool
     */
    public function getCreateRedirectFromMessage($data);
}
