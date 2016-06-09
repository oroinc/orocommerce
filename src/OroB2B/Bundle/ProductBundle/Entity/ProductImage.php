<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Model\ExtendProductImage;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_product_image")
 * @Config
 */
class ProductImage extends ExtendProductImage
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product", inversedBy="images")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $product;

    /**
     * @var Collection|ProductImageType[]
     *
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductImageType",
     *     mappedBy="productImage",
     *     indexBy="type",
     *     cascade={"ALL"},
     *     orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $types;

    public function __construct()
    {
        $this->types = new ArrayCollection();
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
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return Collection|ProductImageType[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param ProductImageType $productImageType
     */
    public function addType(ProductImageType $productImageType)
    {
        if (!$this->types->containsKey($productImageType->getType())) {
            $this->types->set($productImageType->getType(), $productImageType);
            $productImageType->setProductImage($this);
        }
    }

    /**
     * @param ProductImageType $productImageType
     */
    public function removeType(ProductImageType $productImageType)
    {
        if ($this->types->containsKey($productImageType->getType())) {
            $this->types->remove($productImageType->getType());
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasType($type)
    {
        return $this->types->containsKey($type);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getImage() ? $this->getImage()->getFilename() : sprintf('ProductImage #%d', $this->getId());
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            foreach ($this->types as $type) {
                $typeCopy = clone $type;
                $typeCopy->setProductImage($this);
                $this->types[$typeCopy->getType()] = $typeCopy;
            }
        }
    }
}
