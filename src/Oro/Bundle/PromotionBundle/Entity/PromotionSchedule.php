<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;

/**
* Entity that represents Promotion Schedule
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_promotion_schedule')]
class PromotionSchedule implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Promotion::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Promotion $promotion = null;

    #[ORM\Column(name: 'active_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $activeAt = null;

    #[ORM\Column(name: 'deactivate_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $deactivateAt = null;

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
     * {@inheritdoc}
     */
    public function getScheduleIntervalsHolder()
    {
        return $this->getPromotion();
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
}
