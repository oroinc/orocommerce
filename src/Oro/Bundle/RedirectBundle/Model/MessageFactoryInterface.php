<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

/**
 * Defines the contract for creating and parsing slug generation messages.
 *
 * This interface provides methods for creating message payloads for slug generation operations,
 * both for individual entities and mass operations. It also defines methods for extracting
 * entity information and redirect configuration from message payloads.
 */
interface MessageFactoryInterface
{
    /**
     * @param SluggableInterface $entity
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
