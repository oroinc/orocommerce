<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Combined Price List Currency
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_plist_curr_combined')]
class CombinedPriceListCurrency extends BasePriceListCurrency
{
    /**
     * @var CombinedPriceList|null
     */
    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class, cascade: ['persist'], inversedBy: 'currencies')]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $priceList;
}
