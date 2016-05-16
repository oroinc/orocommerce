<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="orob2b_price_list")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository")
 * @ORM\EntityListeners("OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener")
 * @Config(
 *      routeName="orob2b_pricing_price_list_index",
 *      routeView="orob2b_pricing_price_list_view",
 *      routeUpdate="orob2b_pricing_price_list_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_pricing_price_list_select",
 *              "grid_name"="pricing-price-list-select-grid",
 *          }
 *      }
 * )
 */
class PriceList extends BasePriceList
{
    /**
     * @var bool
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $default = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", options={"default"=true})
     */
    protected $active = true;

    /**
     * @var Collection|ProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\ProductPrice",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $prices;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency",
     *      mappedBy="priceList",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * @var PriceListSchedule[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule",
     *      mappedBy="priceList",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"activeAt" = "ASC"})
     */
    protected $schedules;

    /**
     * @var bool
     * @ORM\Column(name="contain_schedule", type="boolean")
     */
    protected $containSchedule = false;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->schedules = new ArrayCollection();
    }

    /**
     * @param bool $default
     *
     * @return PriceList
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @return ArrayCollection|PriceListSchedule[]
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param ArrayCollection|PriceListSchedule[] $schedules
     * @return $this
     */
    public function setSchedules($schedules)
    {
        $this->schedules = $schedules;

        return $this;
    }

    /**
     * @param PriceListSchedule $schedule
     * @return $this
     */
    public function addSchedule(PriceListSchedule $schedule)
    {
        $schedule->setPriceList($this);
        $this->schedules->add($schedule);
        $this->containSchedule = true;

        return $this;
    }

    /**
     * @param PriceListSchedule $schedule
     * @return $this
     */
    public function removeSchedule(PriceListSchedule $schedule)
    {
        $this->schedules->removeElement($schedule);
        $this->containSchedule = !$this->schedules->isEmpty();

        return $this;
    }

    /**
     * @return boolean
     */
    public function isContainSchedule()
    {
        return $this->containSchedule;
    }

    /**
     * @param boolean $containSchedule
     * @return PriceList
     */
    public function setContainSchedule($containSchedule)
    {
        $this->containSchedule = $containSchedule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new PriceListCurrency();
    }

    /**
     * @param PriceListSchedule $needle
     * @return bool
     */
    public function hasSchedule(PriceListSchedule $needle)
    {
        foreach ($this->getSchedules() as $schedule) {
            if ($schedule->equals($needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
