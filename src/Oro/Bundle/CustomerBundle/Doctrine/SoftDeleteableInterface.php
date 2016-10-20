<?php

namespace Oro\Bundle\CustomerBundle\Doctrine;

interface SoftDeleteableInterface
{
    const FIELD_NAME = 'deletedAt';
    const NAME = 'Oro\Bundle\CustomerBundle\Doctrine\SoftDeleteableInterface';

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
