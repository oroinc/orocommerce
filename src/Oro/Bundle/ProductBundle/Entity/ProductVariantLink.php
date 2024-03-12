<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Entity class describe data in table oro_product_variant_link
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_product_variant_link')]
#[Config]
class ProductVariantLink
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variantLinks')]
    #[ORM\JoinColumn(name: 'parent_product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Product $parentProduct = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'parentVariantLinks')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10, 'identity' => true]])]
    protected ?Product $product = null;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected ?bool $visible = true;

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
        if ($this->id === null) {
            $this->parentProduct->addVariantLink($this);
        }

        return $this;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        if ($this->id === null) {
            $this->product->addParentVariantLink($this);
        }

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
