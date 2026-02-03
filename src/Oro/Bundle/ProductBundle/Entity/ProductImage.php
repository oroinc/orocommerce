<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroProductBundle_Entity_ProductImage;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;

/**
 * Represents different types of product image, such as a image is used in the product details view,
 * a image is shown in the catalog listing, an additional product picture, etc.
 *
 *
 * @method File getImage()
 * @method ProductImage setImage(File $image)
 * @mixin OroProductBundle_Entity_ProductImage
 */
#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\Table(name: 'oro_product_image')]
#[ORM\HasLifecycleCallbacks]
#[Config]
class ProductImage implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $product = null;

    /**
     * @var Collection<int, ProductImageType>
     */
    #[ORM\OneToMany(
        mappedBy: 'productImage',
        targetEntity: ProductImageType::class,
        cascade: ['ALL'],
        orphanRemoval: true,
        indexBy: 'type'
    )]
    #[ConfigField(defaultValues: ['importexport' => ['full' => true]])]
    protected ?Collection $types = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updatedAt = null;

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

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->setUpdatedAtToNow();
    }

    #[ORM\PreUpdate]
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
