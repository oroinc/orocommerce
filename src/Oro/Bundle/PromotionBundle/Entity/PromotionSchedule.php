<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oropromotion_schedule")
 */
class PromotionSchedule
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Promotion
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\Promotion", inversedBy="schedules")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $promotion;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="active_at", type="datetime", nullable=true)
     */
    protected $activeAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deactivate_at", type="datetime", nullable=true)
     */
    protected $deactivateAt;

    /**
     * @param \DateTime|null $activeAt
     * @param \DateTime|null $deactivateAt
     */
    public function __construct(\DateTime $activeAt = null, \DateTime $deactivateAt = null)
    {
        $this->activeAt = $activeAt;
        $this->deactivateAt = $deactivateAt;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param Promotion $promotion
     * @return $this
     */
    public function setPromotion(Promotion $promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * @param \DateTime|null $activeAt
     * @return $this
     */
    public function setActiveAt(\DateTime $activeAt = null)
    {
        $this->activeAt = $activeAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeactivateAt()
    {
        return $this->deactivateAt;
    }

    /**
     * @param \DateTime|null $deactivateAt
     * @return $this
     */
    public function setDeactivateAt(\DateTime $deactivateAt = null)
    {
        $this->deactivateAt = $deactivateAt;

        return $this;
    }
}
