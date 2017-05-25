<?php

namespace Oro\Bundle\ProductBundle\Entity\RelatedItems;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(
 *     name="oro_product_related_products"
 * )
 */
class RelatedProducts
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
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="related_product_id", referencedColumnName="id")
     */
    protected $relatedProduct;

    /**
     * @return int
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
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Product
     */
    public function getRelatedProduct()
    {
        return $this->relatedProduct;
    }

    /**
     * @param Product $relatedProduct
     * @return $this
     */
    public function setRelatedProduct(Product $relatedProduct)
    {
        $this->relatedProduct = $relatedProduct;

        return $this;
    }
}
