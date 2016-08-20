<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_product_variant_link")
 * @Config
 */
class ProductVariantLink
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="variantLinks")
     * @ORM\JoinColumn(name="parent_product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false))
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $parentProduct;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $product;

    /**
     * @var bool
     * @ORM\Column(name="visible", type="boolean", nullable=false, options={"default"=true})
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     * )
     */
    protected $visible = true;

    /**
     * @param Product $parentProduct
     * @param Product $product
     */
    public function __construct(Product $parentProduct = null, Product $product = null)
    {
        $this->parentProduct = $parentProduct;
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->product;
    }

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
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param Product $parentProduct
     * @return $this
     */
    public function setParentProduct(Product $parentProduct)
    {
        $this->parentProduct = $parentProduct;

        return $this;
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
     * @param boolean $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = (bool) $visible;

        return $this;
    }
}
