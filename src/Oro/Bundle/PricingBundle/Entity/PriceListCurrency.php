<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @method PriceList getPriceList()
 *
 * @ORM\Table(name="oro_price_list_currency")
 * @ORM\Entity()
 */
class PriceListCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList",
     *      inversedBy="currencies",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;
}
