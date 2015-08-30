<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_product_variant_link")
 * @Config()
 */
class ProductVariantLink
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="variantLinks")
     * @ORM\JoinColumn(name="parent_product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false))
     */
    protected $parentProduct;

    /**
     * @var Product
     * @ORM\OneToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $product;

    /**
     * @var boolean
     * @ORM\Column(name="linked", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $linked = true;

    /**
     * @param Product $parentProduct
     * @param Product $product
     */
    public function __construct(Product $parentProduct, Product $product)
    {
        $this->parentProduct = $parentProduct;
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getParentProduct()
    {
        return $this->parentProduct;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return boolean
     */
    public function isLinked()
    {
        return $this->linked;
    }

}
