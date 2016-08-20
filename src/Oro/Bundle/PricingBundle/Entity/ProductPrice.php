<?php

namespace Oro\Bundle\PricingBundle\Entity;

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
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository")
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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList", inversedBy="prices")
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

    /**
     * @var PriceRule
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceRule")
     * @ORM\JoinColumn(name="price_rule_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    protected $priceRule;

    /**
     * @return PriceRule
     */
    public function getPriceRule()
    {
        return $this->priceRule;
    }

    /**
     * @param PriceRule $priceRule
     * @return $this
     */
    public function setPriceRule($priceRule)
    {
        $this->priceRule = $priceRule;
        
        return $this;
    }
}
