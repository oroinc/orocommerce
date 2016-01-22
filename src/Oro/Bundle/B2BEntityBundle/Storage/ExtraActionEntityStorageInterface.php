<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

interface ExtraActionEntityStorageInterface
{
    /**
     * @param ObjectIdentifierAwareInterface $entity
     * @return void
     */
    public function scheduleForExtraInsert(ObjectIdentifierAwareInterface $entity);

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
     * @param ObjectIdentifierAwareInterface $entity
     * @return bool
     */
    public function isScheduledForInsert(ObjectIdentifierAwareInterface $entity);
}
