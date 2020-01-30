<?php

namespace Oro\Bundle\ProductBundle\Entity\RelatedItem;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemEntityInterface;

/**
 * Representation of relations between related products
 *
 * @ORM\Table(
 *     name="oro_product_related_products",
 *     indexes={
 *          @ORM\Index(name="idx_oro_product_related_products_product_id", columns={"product_id"}),
 *          @ORM\Index(name="idx_oro_product_related_products_related_item_id", columns={"related_item_id"})
 *     },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="idx_oro_product_related_products_unique",
 *              columns={"product_id", "related_item_id"}
 *          )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository")
 * @Config(mode="hidden")
 */
class RelatedProduct implements RelatedItemEntityInterface
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
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          },
     *      }
     * )
     */
    protected $product;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="related_item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          },
     *      }
     * )
     */
    protected $relatedItem;

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
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
