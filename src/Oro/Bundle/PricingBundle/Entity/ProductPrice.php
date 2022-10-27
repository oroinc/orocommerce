<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * Entity holds information about product price
 *
 * @ORM\Table(
 *      name="oro_price_product",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_pricing_price_list_uidx",
 *              columns={"product_id", "price_list_id", "quantity", "unit_code", "currency"}
 *          )
 *      },
 *     indexes={
 *         @ORM\Index(
 *              name="oro_price_version_idx",
 *              columns={
 *                  "price_list_id",
 *                  "version",
 *                  "product_id"
 *              }
 *         ),
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-usd"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "sharding"={
 *              "discrimination_field"="priceList"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @method PriceList getPriceList()
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
     *          },
     *          "dataaudit"={
     *              "auditable"=true,
     *              "propagate"=true
     *          }
     *      }
     * )
     */
    protected $priceList;

    /**
     * @var PriceRule
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceRule")
     * @ORM\JoinColumn(name="price_rule_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $priceRule;

    /**
     * @var int
     *
     * @ORM\Column(name="version", type="integer", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $version;

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

    /**
     * @return int|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return ProductPrice
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }
}
