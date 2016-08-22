<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

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
