<?php

namespace Oro\Bundle\ProductBundle\Entity\RelatedItem;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemEntityInterface;

/**
 * @ORM\Table(
 *     name="oro_product_upsell_product",
 *     indexes={
 *         @ORM\Index(name="idx_oro_product_upsell_product_product_id", columns={"product_id"}),
 *         @ORM\Index(name="idx_oro_product_upsell_product_related_item_id", columns={"related_item_id"}),
 *     },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="idx_oro_product_upsell_product_unique",
 *              columns={"product_id", "related_item_id"}
 *          )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository")
*/
class UpsellProduct implements RelatedItemEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
    */
    protected $product;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="related_item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $relatedItem;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRelatedItem()
    {
        return $this->relatedItem;
    }

    /**
     * {@inheritDoc}
     */
    public function setRelatedItem(Product $product)
    {
        $this->relatedItem = $product;

        return $this;
    }
}
