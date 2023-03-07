<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Represents different types of product image, such as a image is used in the product details view,
 * a image is shown in the catalog listing, an additional product picture, etc.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_product_image")
 * @ORM\HasLifecycleCallbacks
 * @Config
 *
 * @method File getImage()
 * @method ProductImage setImage(File $image)
 */
class ProductImage implements ExtendEntityInterface
{
    use ExtendEntityTrait;

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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product", inversedBy="images")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $product;

    /**
     * @var Collection|ProductImageType[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ProductBundle\Entity\ProductImageType",
     *     mappedBy="productImage",
     *     indexBy="type",
     *     cascade={"ALL"},
     *     orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "full"=true
     *          }
     *     }
     * )
     */
    protected $types;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

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

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return ProductImageType[]|Collection
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param $type
     * @return mixed|null
     */
    public function getType($type)
    {
        return $this->types->get($type);
    }

    /**
     * @param ArrayCollection $types
     * @return $this
     */
    public function setTypes(ArrayCollection $types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * @param ProductImageType|string $type
     * @return null|$this
     */
    public function addType($type)
    {
        if ($type instanceof ProductImageType && !$this->types->contains($type)) {
            $this->types->add($type);
            $type->setProductImage($this);
            $this->setUpdatedAtToNow();

            return $this;
        }

        if (!$this->types->containsKey($type)) {
            $productImageType = new ProductImageType($type);
            $productImageType->setProductImage($this);
            $this->types->set($type, $productImageType);

            $this->setUpdatedAtToNow();
        }
    }

    /**
     * @param ProductImageType|string $type
     */
    public function removeType($type)
    {
        if ($type instanceof ProductImageType) {
            $type = $type->getType();
        }

        if ($this->hasType($type)) {
            $this->types->remove($type);

            $this->setUpdatedAtToNow();
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

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setUpdatedAtToNow()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUpdatedAtToNow();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdatedAtToNow();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getImage()
            ? (string)$this->getImage()->getFilename()
            : sprintf('ProductImage #%d', $this->getId());
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $types = $this->getTypes();
            $this->types = new ArrayCollection();
            foreach ($types as $type) {
                $this->addType($type->getType());
            }
            $this->cloneExtendEntityStorage();
        }
    }
}
