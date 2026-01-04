<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;

/**
* Entity that represents Price List Website Fallback
*
*/
#[ORM\Entity(repositoryClass: PriceListWebsiteFallbackRepository::class)]
#[ORM\Table(name: 'oro_price_list_website_fb')]
#[ORM\UniqueConstraint(name: 'oro_price_list_website_fb_unq', columns: ['website_id'])]
class PriceListWebsiteFallback extends PriceListFallback
{
    public const CONFIG = 0;
    public const CURRENT_WEBSITE_ONLY = 1;
}
