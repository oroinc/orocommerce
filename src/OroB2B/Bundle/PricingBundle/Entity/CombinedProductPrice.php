<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="orob2b_price_product_combined",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_combined_price_uidx",
 *              columns={"product_id", "combined_price_list_id", "quantity", "unit_code", "currency"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")
 */
class CombinedProductPrice extends BaseProductPrice
{
    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList", inversedBy="prices")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;

    /**
     * @var boolean
     *
     * @ORM\Column(name="merge_allowed", type="boolean", nullable=false)
     */
    protected $mergeAllowed = true;
}
