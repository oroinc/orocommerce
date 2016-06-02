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
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="images")
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
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductImageType")
     * @ORM\JoinTable(
     *      name="orob2b_product_image_to_type",
     *      joinColumns={
     *          @ORM\JoinColumn(name="product_image_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="product_image_type_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
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
     * @return array
     */
    public function getTypes()
    {
        return (array) $this->types;
    }

    /**
     * @param Collection $types
     */
    public function setTypes(Collection $types)
    {
        $this->types = $types;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasType($type)
    {
        return in_array($type, $this->getTypes());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getImage() ? $this->getImage()->getFilename() : sprintf('ProductImage #%d', $this->getId());
    }
}
