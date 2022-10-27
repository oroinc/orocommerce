<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

/**
 * This entity represents price list with price attributes
 *
 * @ORM\Table(name="oro_price_attribute_pl")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository")
 * @Config(
 *      routeName="oro_pricing_price_attribute_price_list_index",
 *      routeView="oro_pricing_price_attribute_price_list_view",
 *      routeUpdate="oro_pricing_price_attribute_price_list_update",
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class PriceAttributePriceList extends BasePriceList implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    /**
     * @var Collection|PriceAttributeProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice",
     *      mappedBy="priceList",
     *      fetch="EXTRA_LAZY"
     * )
     */
    protected $prices;

    /**
     * @var PriceAttributeCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceAttributeCurrency",
     *      mappedBy="priceList",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $fieldName;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_enabled_in_export", type="boolean", options={"default"=false})
     */
    protected $enabledInExport = false;

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName): self
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function isEnabledInExport(): bool
    {
        return $this->enabledInExport;
    }

    /**
     * @param bool $enabledInExport
     * @return $this
     */
    public function setEnabledInExport(bool $enabledInExport): self
    {
        $this->enabledInExport = $enabledInExport;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new PriceAttributeCurrency();
    }
}
