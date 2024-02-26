<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents product kit item label.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_product_prod_kit_item_label')]
#[ORM\Index(columns: ['fallback'], name: 'idx_product_prod_kit_fallback')]
#[ORM\Index(columns: ['string'], name: 'idx_product_prod_kit_string')]
#[Config]
class ProductKitItemLabel extends AbstractLocalizedFallbackValue
{
    #[ORM\Column(name: 'string', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $string = null;

    #[ORM\ManyToOne(targetEntity: ProductKitItem::class, inversedBy: 'labels')]
    #[ORM\JoinColumn(name: 'product_kit_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ProductKitItem $kitItem = null;

    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function setKitItem(?ProductKitItem $kitItem): self
    {
        $this->kitItem = $kitItem;

        return $this;
    }
}
