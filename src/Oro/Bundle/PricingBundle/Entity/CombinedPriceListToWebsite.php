<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;

/**
* Entity that represents Combined Price List To Website
*
*/
#[ORM\Entity(repositoryClass: CombinedPriceListToWebsiteRepository::class)]
#[ORM\Table(name: 'oro_cmb_price_list_to_ws')]
#[ORM\UniqueConstraint(name: 'oro_cpl_to_ws_unq', columns: ['website_id'])]
class CombinedPriceListToWebsite extends BaseCombinedPriceListRelation
{
}
