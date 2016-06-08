<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_product_attr_currency")
 * @ORM\Entity()
 */
class PriceAttributeCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceAttribute
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceAttribute",
     *      inversedBy="currencies",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="price_attribute_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceAttribute;
}
