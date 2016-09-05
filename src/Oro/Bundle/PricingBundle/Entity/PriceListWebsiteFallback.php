<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_price_list_website_fb",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_price_list_website_fb_unq", columns={
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository")
 */
class PriceListWebsiteFallback extends PriceListFallback
{
    const CONFIG = 0;
    const CURRENT_WEBSITE_ONLY = 1;
}
