<?php

namespace OroB2B\src\Oro\Bundle\B2BEntityBundle\Storage;

interface ExtraInsertEntityStorageInterface
{
    /**
     * @param mixed $entity
     * @return void
     */
    public function scheduleForExtraInsert($entity);
}
