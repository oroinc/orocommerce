<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;

/**
 * Entity to store Prices of Product Price attributes
 */
#[ORM\Entity(repositoryClass: PriceAttributeProductPriceRepository::class)]
#[ORM\Table(name: 'oro_price_attribute_price')]
#[ORM\UniqueConstraint(
    name: 'oro_pricing_price_attribute_uidx',
    columns: ['product_id', 'price_attribute_pl_id', 'quantity', 'unit_code', 'currency']
)]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-usd']])]
class PriceAttributeProductPrice extends BaseProductPrice
{
    /**
     * @var int|null
     */
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $id;

    /**
     * @var PriceAttributePriceList|null
     */
    #[ORM\ManyToOne(targetEntity: PriceAttributePriceList::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(name: 'price_attribute_pl_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 15, 'identity' => true]])]
    protected $priceList;

    public function __construct()
    {
        $this->setQuantity(1);
    }
}
