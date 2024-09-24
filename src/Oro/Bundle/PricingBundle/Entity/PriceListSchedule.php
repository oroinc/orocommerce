<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;

/**
* Entity that represents Price List Schedule
*
*/
#[ORM\Entity(repositoryClass: PriceListScheduleRepository::class)]
#[ORM\Table(name: 'oro_price_list_schedule')]
class PriceListSchedule implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

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
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    #[\Override]
    public function getScheduleIntervalsHolder()
    {
        return $this->getPriceList();
    }

    /**
     * @param PriceList $priceList
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;
        $this->priceList->setContainSchedule(true);

        return $this;
    }

    /**
     * @param PriceListSchedule $compared
     * @return bool
     */
    public function equals(PriceListSchedule $compared)
    {
        return $compared->getPriceList() === $this->getPriceList()
            && $compared->getActiveAt() == $this->getActiveAt()
            && $compared->getDeactivateAt() == $this->getDeactivateAt();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5(json_encode([
            'priceList' => $this->priceList ? spl_object_hash($this->priceList) : null,
            'activeAt' => $this->activeAt,
            'deactivateAt' => $this->deactivateAt
        ]));
    }
}
