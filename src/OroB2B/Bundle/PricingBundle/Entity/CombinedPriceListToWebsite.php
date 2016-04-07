<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_cmb_price_list_to_ws")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository")
 */
class CombinedPriceListToWebsite extends BaseCombinedPriceListRelation
{

}
