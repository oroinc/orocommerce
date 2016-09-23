<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_product_attr_currency")
 * @ORM\Entity()
 */
class PriceAttributeCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceAttributePriceList
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList",
     *      inversedBy="currencies",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="price_attribute_pl_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;
}
