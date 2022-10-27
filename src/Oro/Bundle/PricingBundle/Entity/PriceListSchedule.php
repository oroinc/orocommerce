<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;

/**
 * @ORM\Table(name="oro_price_list_schedule")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository")
 */
class PriceListSchedule implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList", inversedBy="schedules")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $priceList;

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

    /**
     * {@inheritdoc}
     */
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
