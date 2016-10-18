<?php

namespace Oro\Bundle\CustomerBundle\Doctrine;

trait SoftDeleteableTrait
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    protected $deletedAt;

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime|null $date
     * @return $this
     */
    public function setDeletedAt(\DateTime $date = null)
    {
        $this->deletedAt = $date;

        return $this;
    }
}
