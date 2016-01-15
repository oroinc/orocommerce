<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

interface ExtraActionEntityStorageInterface
{
    /**
     * @param mixed $entity
     * @return void
     */
    public function scheduleForExtraInsert($entity);

    /**
     * @return bool
     */
    public function hasScheduledForInsert();

    /**
     * @return void
     */
    public function clearScheduledForInsert();

    /**
     * @return array
     */
    public function getScheduledForInsert();

    /**
     * @param mixed $entity
     * @return bool
     */
    public function isScheduledForInsert($entity);
}
