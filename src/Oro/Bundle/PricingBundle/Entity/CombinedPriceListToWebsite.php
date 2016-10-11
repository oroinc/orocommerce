<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="oro_cmb_price_list_to_ws",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="oro_cpl_to_ws_unq", columns={"website_id"})}
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository")
 */
class CombinedPriceListToWebsite extends BaseCombinedPriceListRelation
{

}
