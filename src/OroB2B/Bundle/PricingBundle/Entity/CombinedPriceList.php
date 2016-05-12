<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_list_combined")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository")
 */
class CombinedPriceList extends BasePriceList
{
    /**
     * @var bool
     *
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    protected $enabled = false;

    /**
     * @var Collection|CombinedProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $prices;

    /**
     * @var CombinedPriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListCurrency",
     *      mappedBy="priceList",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_prices_calculated", type="boolean")
     */
    protected $pricesCalculated = false;

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return CombinedPriceList
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new CombinedPriceListCurrency();
    }

    /**
     * @return boolean
     */
    public function isPricesCalculated()
    {
        return $this->pricesCalculated;
    }

    /**
     * @param boolean $pricesCalculated
     * @return $this
     */
    public function setPricesCalculated($pricesCalculated)
    {
        $this->pricesCalculated = $pricesCalculated;

        return $this;
    }
}
