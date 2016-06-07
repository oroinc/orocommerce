<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_attribute")
 * @ORM\Entity()
 */
class PriceAttribute extends BasePriceList
{
    /**
     * @var Collection|PriceAttributeProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice",
     *      mappedBy="priceAttribute",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $prices;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceAttributeCurrency",
     *      mappedBy="priceAttribute",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new PriceAttributeCurrency();
    }
}
