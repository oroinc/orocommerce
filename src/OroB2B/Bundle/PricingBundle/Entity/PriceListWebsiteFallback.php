<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_list_website_fb")
 * @ORM\Entity()
 */
class PriceListWebsiteFallback extends PriceListFallback
{
    const CURRENT_WEBSITE_ONLY = 0;
    const CONFIG = 1;
}
