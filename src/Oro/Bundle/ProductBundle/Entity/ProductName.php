<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents product name
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_product_prod_name')]
#[ORM\Index(columns: ['fallback'], name: 'idx_product_prod_name_fallback')]
#[ORM\Index(columns: ['string'], name: 'idx_product_prod_name_string')]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class ProductName extends AbstractLocalizedFallbackValue
{
    #[ORM\Column(name: 'string', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => false]])]
    protected ?string $string = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'names')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Product $product = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param null|Product $product
     * @return $this
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
