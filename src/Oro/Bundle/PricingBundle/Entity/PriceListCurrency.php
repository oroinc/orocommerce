<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Price List Currency entity
 * @method PriceList getPriceList()
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_price_list_currency')]
class PriceListCurrency extends BasePriceListCurrency
{
    /**
     * @var PriceList|null
     */
    #[ORM\ManyToOne(targetEntity: PriceList::class, cascade: ['persist'], inversedBy: 'currencies')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $priceList;
}
