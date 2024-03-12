<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

/**
 * Entity holds information about product price
 *
 * @method PriceList getPriceList()
 */
#[ORM\Entity(repositoryClass: ProductPriceRepository::class)]
#[ORM\Table(name: 'oro_price_product')]
#[ORM\Index(columns: ['price_list_id', 'version', 'product_id'], name: 'oro_price_version_idx')]
#[ORM\UniqueConstraint(
    name: 'oro_pricing_price_list_uidx',
    columns: ['product_id', 'price_list_id', 'quantity', 'unit_code', 'currency']
)]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-usd'],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'sharding' => ['discrimination_field' => 'priceList'],
        'dataaudit' => ['auditable' => true]
    ]
)]
class ProductPrice extends BaseProductPrice
{
    /**
     * @var PriceList|null
     */
    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['identity' => true],
            'dataaudit' => ['auditable' => true, 'propagate' => true]
        ]
    )]
    protected $priceList;

    #[ORM\ManyToOne(targetEntity: PriceRule::class)]
    #[ORM\JoinColumn(name: 'price_rule_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?PriceRule $priceRule = null;

    #[ORM\Column(name: 'version', type: Types::INTEGER, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $version = null;

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
