<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(
 *      name="orob2b_price_product",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_pricing_price_list_uidx",
 *              columns={"product_id", "price_list_id", "quantity", "unit_code", "currency"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository")
 * @ORM\EntityListeners("OroB2B\Bundle\PricingBundle\Entity\EntityListener\ProductPriceEntityListener")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-usd"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 */
class ProductPrice extends BaseProductPrice
{
    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList", inversedBy="prices")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     **/
    protected $priceList;
}
