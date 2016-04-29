<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="orob2b_price_list")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository")
 * @Config(
 *      routeName="orob2b_pricing_price_list_index",
 *      routeView="orob2b_pricing_price_list_view",
 *      routeUpdate="orob2b_pricing_price_list_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_pricing_price_list_select",
 *              "grid_name"="pricing-price-list-select-grid",
 *          }
 *      }
 * )
 */
class PriceList extends BasePriceList
{
    /**
     * @var bool
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $default = false;

    /**
     * @var Collection|ProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\ProductPrice",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $prices;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency",
     *      mappedBy="priceList",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * @param bool $default
     *
     * @return PriceList
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new PriceListCurrency();
    }
}
