<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;

/**
* Entity that represents Price List To Website
*
*/
#[ORM\Entity(repositoryClass: PriceListToWebsiteRepository::class)]
#[ORM\Table(name: 'oro_price_list_to_website')]
#[ORM\UniqueConstraint(name: 'oro_price_list_to_website_unique_key', columns: ['price_list_id', 'website_id'])]
class PriceListToWebsite extends BasePriceListRelation
{
}
