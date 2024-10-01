<?php

namespace Oro\Bundle\ProductBundle\Entity\RelatedItem;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemEntityInterface;

/**
 * Representation of relations between related products
 */
#[ORM\Entity(repositoryClass: RelatedProductRepository::class)]
#[ORM\Table(name: 'oro_product_related_products')]
#[ORM\Index(columns: ['product_id'], name: 'idx_oro_product_related_products_product_id')]
#[ORM\Index(columns: ['related_item_id'], name: 'idx_oro_product_related_products_related_item_id')]
#[ORM\UniqueConstraint(name: 'idx_oro_product_related_products_unique', columns: ['product_id', 'related_item_id'])]
#[Config(mode: 'hidden')]
class RelatedProduct implements RelatedItemEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'related_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Product $relatedItem = null;

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    #[\Override]
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    #[\Override]
    public function getRelatedItem()
    {
        return $this->relatedItem;
    }

    #[\Override]
    public function setRelatedItem(Product $product)
    {
        $this->relatedItem = $product;

        return $this;
    }
}
