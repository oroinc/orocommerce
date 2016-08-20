<?php

namespace Oro\Bundle\AccountBundle\Doctrine;

interface SoftDeleteableInterface
{
    const FIELD_NAME = 'deletedAt';
    const NAME = 'Oro\Bundle\AccountBundle\Doctrine\SoftDeleteableInterface';

    /**
     * @return \DateTime
     */
    public function getDeletedAt();

    /**
     * @param \DateTime|null $date
     * @return $this
     */
    public function setDeletedAt(\DateTime $date = null);
}
