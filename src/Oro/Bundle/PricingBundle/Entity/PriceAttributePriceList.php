<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;

/**
 * This entity represents price list with price attributes
 */
#[ORM\Entity(repositoryClass: PriceAttributePriceListRepository::class)]
#[ORM\Table(name: 'oro_price_attribute_pl')]
#[Config(
    routeName: 'oro_pricing_price_attribute_price_list_index',
    routeView: 'oro_pricing_price_attribute_price_list_view',
    routeUpdate: 'oro_pricing_price_attribute_price_list_update',
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ]
    ]
)]
class PriceAttributePriceList extends BasePriceList implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    /**
     * @var Collection<int, PriceAttributeProductPrice>
     */
    #[ORM\OneToMany(mappedBy: 'priceList', targetEntity: PriceAttributeProductPrice::class, fetch: 'EXTRA_LAZY')]
    protected ?Collection $prices = null;

    /**
     * @var Collection<int, PriceAttributeCurrency>
     */
    #[ORM\OneToMany(
        mappedBy: 'priceList',
        targetEntity: PriceAttributeCurrency::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    protected ?Collection $currencies = null;

    #[ORM\Column(name: 'field_name', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $fieldName = null;

    #[ORM\Column(name: 'is_enabled_in_export', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $enabledInExport = false;

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
