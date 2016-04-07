<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_list_currency")
 * @ORM\Entity()
 */
class PriceListCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList",
     *      inversedBy="currencies",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;
}
