<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(
 *      name="oro_price_attribute_price",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_pricing_price_attribute_uidx",
 *              columns={"product_id", "price_attribute_pl_id", "quantity", "unit_code", "currency"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-usd"
 *          }
 *      }
 * )
 */
class PriceAttributeProductPrice extends BaseProductPrice
{
    /**
     * @var PriceAttributePriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList", inversedBy="prices")
     * @ORM\JoinColumn(name="price_attribute_pl_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;
}
