<?php

namespace Oro\Bundle\ProductBundle\Entity\RelatedItem;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemEntityInterface;

/**
* Entity that represents Upsell Product
*
*/
#[ORM\Entity(repositoryClass: UpsellProductRepository::class)]
#[ORM\Table(name: 'oro_product_upsell_product')]
#[ORM\Index(columns: ['product_id'], name: 'idx_oro_product_upsell_product_product_id')]
#[ORM\Index(columns: ['related_item_id'], name: 'idx_oro_product_upsell_product_related_item_id')]
#[ORM\UniqueConstraint(name: 'idx_oro_product_upsell_product_unique', columns: ['product_id', 'related_item_id'])]
class UpsellProduct implements RelatedItemEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'related_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $relatedItem = null;

    /**
     * @return int
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

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
