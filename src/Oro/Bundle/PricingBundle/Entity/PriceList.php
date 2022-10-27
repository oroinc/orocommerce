<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalsAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\PricingBundle\Model\ExtendPriceList;

/**
 * Entity holds price list data.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @ORM\Table(name="oro_price_list")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository")
 * @Config(
 *      routeName="oro_pricing_price_list_index",
 *      routeView="oro_pricing_price_list_view",
 *      routeUpdate="oro_pricing_price_list_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType",
 *              "grid_name"="pricing-price-list-select-grid",
 *          }
 *      }
 * )
 */
class PriceList extends ExtendPriceList implements ScheduleIntervalsAwareInterface
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
     * @var bool
     *
     * @ORM\Column(name="actual", type="boolean", options={"default"=true})
     */
    protected $actual = true;

    /**
     * @var Collection|ProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\ProductPrice",
     *      mappedBy="priceList",
     *      fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $prices;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceListCurrency",
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
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceListSchedule",
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
     * @var string
     * @ORM\Column(name="product_assignment_rule", type="text", nullable=true)
     */
    protected $productAssignmentRule;

    /**
     * @var Collection|PriceRule[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceRule",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"priority" = "ASC"})
     **/
    protected $priceRules;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->schedules = new ArrayCollection();
        $this->priceRules = new ArrayCollection();

        parent::__construct();
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
        $this->refreshContainSchedule();

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

    public function refreshContainSchedule()
    {
        $this->setContainSchedule(!$this->schedules->isEmpty());
    }

    /**
     * @return ArrayCollection|PriceRule[]
     */
    public function getPriceRules()
    {
        return $this->priceRules;
    }

    /**
     * @param ArrayCollection|PriceRule[] $priceRules
     * @return $this
     */
    public function setPriceRules($priceRules)
    {
        $this->priceRules = $priceRules;

        return $this;
    }

    /**
     * @param PriceRule $priceRule
     * @return $this
     */
    public function addPriceRule(PriceRule $priceRule)
    {
        $priceRule->setPriceList($this);
        $this->priceRules->add($priceRule);

        return $this;
    }

    /**
     * @param PriceRule $priceRule
     * @return $this
     */
    public function removePriceRule(PriceRule $priceRule)
    {
        $this->priceRules->removeElement($priceRule);

        return $this;
    }

    /**
     * @return string
     */
    public function getProductAssignmentRule()
    {
        return $this->productAssignmentRule;
    }

    /**
     * @param string $productAssignmentRule
     */
    public function setProductAssignmentRule($productAssignmentRule)
    {
        $this->productAssignmentRule = $productAssignmentRule;
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

    /**
     * @return boolean
     */
    public function isActual()
    {
        return $this->actual;
    }

    /**
     * @param boolean $actual
     * @return $this
     */
    public function setActual($actual)
    {
        $this->actual = $actual;

        return $this;
    }

    /**
     * Set API currencies
     *
     * @param string[]|null $currencies
     *
     * @return PriceList
     */
    public function setPriceListCurrencies($currencies): self
    {
        if (!$currencies) {
            $currencies = [];
        }

        $this->setCurrencies($currencies);

        return $this;
    }

    /**
     * Get API currencies
     *
     * @return string[]
     */
    public function getPriceListCurrencies(): array
    {
        return $this->getCurrencies();
    }
}
