<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(
 *     name="oro_product_upsell_products",
 *     indexes={
 *         @ORM\Index(name="idx_oro_product_upsell_products_product_id", columns={"product_id"}),
 *         @ORM\Index(name="idx_oro_product_upsell_products_upsell_product_id", columns={"upsell_product_id"}),
 *         @ORM\Index(name="idx_oro_product_upsell_products_unique", columns={"product_id", "upsell_product_id"})
 *     }
 * )
*/
class UpsellProducts
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
     * @ORM\JoinColumn(name="upsell_product_id", referencedColumnName="id")
     */
    protected $upsellProduct;
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
    public function getUpsellProduct()
    {
        return $this->upsellProduct;
    }
    /**
     * @param Product $upsellProduct
     * @return $this
     */
    public function setUpsellProduct(Product $upsellProduct)
    {
        $this->upsellProduct = $upsellProduct;
        return $this;
    }
}
