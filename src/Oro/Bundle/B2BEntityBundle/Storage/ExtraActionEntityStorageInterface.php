<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

interface ExtraActionEntityStorageInterface
{
    /**
     * @param ObjectIdentifierAwareInterface|object $entity
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
     * @param  ObjectIdentifierAwareInterface|object $entity
     * @return bool
     */
    public function isScheduledForInsert($entity);
}
