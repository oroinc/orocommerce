<?php

namespace Oro\Bundle\CommerceEntityBundle\Storage;

/**
 * Defines the contract for storing entities scheduled for extra insert operations.
 */
interface ExtraActionEntityStorageInterface
{
    /**
     * @param object $entity
     * @return void
     */
    public function scheduleForExtraInsert($entity);

    /**
     * @return void
     */
    public function clearScheduledForInsert();

    /**
     * @param string|null $className
     * @return array
     */
    public function getScheduledForInsert($className = null);
}
