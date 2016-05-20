<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository")
 * @ORM\Table(
 *      name="orob2b_price_product_minimal",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_minimal_price_uidx",
 *              columns={"product_id", "combined_price_list_id", "currency"}
 *          )
 *      }
 * )
 */
class MinimalProductPrice extends BaseProductPrice
{
    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;
}
