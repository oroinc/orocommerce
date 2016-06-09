<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="orob2b_price_attribute_price",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_pricing_price_attribute_uidx",
 *              columns={"product_id", "price_attribute_pl_id", "quantity", "unit_code", "currency"}
 *          )
 *      }
 * )
 * @ORM\Entity()
 */
class PriceAttributeProductPrice extends BaseProductPrice
{
    /**
     * @var PriceAttributePriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList", inversedBy="prices")
     * @ORM\JoinColumn(name="price_attribute_pl_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;
}
