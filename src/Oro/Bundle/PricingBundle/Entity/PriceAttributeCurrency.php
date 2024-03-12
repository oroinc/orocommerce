<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Price Attribute Currency
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_product_attr_currency')]
class PriceAttributeCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceAttributePriceList|null
     */
    #[ORM\ManyToOne(targetEntity: PriceAttributePriceList::class, cascade: ['persist'], inversedBy: 'currencies')]
    #[ORM\JoinColumn(name: 'price_attribute_pl_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $priceList;
}
