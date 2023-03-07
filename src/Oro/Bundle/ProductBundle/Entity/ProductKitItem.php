<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Model\ExtendProductKitItem;

/**
 * Represents a product kit item.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository")
 * @ORM\Table(name="oro_product_kit_item")
 * @ORM\HasLifecycleCallbacks
 * @Config()
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitItem extends ExtendProductKitItem implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @var Collection<ProductKitItemLabel>
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductKitItemLabel",
     *     mappedBy="productKitItem",
     *     cascade={"ALL"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $labels;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", options={"default"=0})
     */
    protected int $sortOrder = 0;

    /**
     * @var Product|null
     *
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="kitItems")
     * @ORM\JoinColumn(name="product_kit_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Product $productKit = null;

    /**
     * @var Collection<ProductKitItemProduct>
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductKitItemProduct",
     *     mappedBy="kitItem",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @OrderBy({"sortOrder"="ASC"})
     */
    protected Collection $kitItemProducts;

    /**
     * @var bool
     *
     * @ORM\Column(name="optional", type="boolean", options={"default"=false})
     */
    protected bool $optional = false;

    /**
     * @var float|null
     *
     * @ORM\Column(name="minimum_quantity", type="float", nullable=true)
     */
    protected ?float $minimumQuantity = null;

    /**
     * @var float|null
     *
     * @ORM\Column(name="maximum_quantity", type="float", nullable=true)
     */
    protected ?float $maximumQuantity = null;

    /**
     * @ORM\ManyToOne(targetEntity="ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", onDelete="SET NULL")
     */
    protected ?ProductUnit $productUnit = null;

    /**
     * @var Collection<ProductUnitPrecision>
     *
     * @ORM\ManyToMany(targetEntity="ProductUnitPrecision")
     * @ORM\JoinTable(
     *     name="oro_product_kit_unit_precisions",
     *     joinColumns={
     *          @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="product_unit_precision_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     */
    protected Collection $referencedUnitPrecisions;

    public function __construct()
    {
        parent::__construct();

        $this->labels = new ArrayCollection();
        $this->kitItemProducts = new ArrayCollection();
        $this->referencedUnitPrecisions = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __toString(): string
    {
        try {
            if ($this->getDefaultLabel()) {
                return (string) $this->getDefaultLabel();
            }

            return (string) $this->id;
        } catch (\LogicException $e) {
            return (string) $this->id;
        }
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->labels = new ArrayCollection();
            $this->products = new ArrayCollection();
            $this->referencedUnitPrecisions = new ArrayCollection();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDefaultLabel($value): self
    {
        parent::setDefaultLabel($value);
        $this->getDefaultLabel()->setProductKitItem($this);

        return $this;
    }

    public function setLabels(array $labels = []): self
    {
        $this->labels->clear();

        foreach ($labels as $label) {
            $this->addLabel($label);
        }

        return $this;
    }

    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(ProductKitItemLabel $label): self
    {
        if (!$this->labels->contains($label)) {
            $label->setProductKitItem($this);
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(ProductKitItemLabel $label): self
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    public function getProductKit(): ?Product
    {
        return $this->productKit;
    }

    public function setProductKit(Product $product): self
    {
        $this->productKit = $product;

        return $this;
    }

    /**
     * @return Collection<ProductKitItem>
     */
    public function getKitItemProducts(): Collection
    {
        return $this->kitItemProducts;
    }

    public function addKitItemProduct(ProductKitItemProduct $productKitItemProduct): self
    {
        if (!$this->kitItemProducts->contains($productKitItemProduct)) {
            $productKitItemProduct->setKitItem($this);
            $this->kitItemProducts->add($productKitItemProduct);
        }

        return $this;
    }

    public function removeKitItemProduct(ProductKitItemProduct $productKitItemProduct): self
    {
        $this->kitItemProducts->removeElement($productKitItemProduct);

        return $this;
    }

    /**
     * @return Collection<Product>
     */
    public function getProducts(): Collection
    {
        return $this->kitItemProducts->map(
            static fn (ProductKitItemProduct $kitItemProduct) => $kitItemProduct->getProduct()
        );
    }

    public function setOptional(bool $optional): self
    {
        $this->optional = $optional;

        return $this;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function setMinimumQuantity(?float $minimumQuantity): self
    {
        $this->minimumQuantity = $minimumQuantity;

        return $this;
    }

    public function getMinimumQuantity(): ?float
    {
        return $this->minimumQuantity;
    }

    public function setMaximumQuantity(?float $maximumQuantity): self
    {
        $this->maximumQuantity = $maximumQuantity;

        return $this;
    }

    public function getMaximumQuantity(): ?float
    {
        return $this->maximumQuantity;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = (int) $sortOrder;

        return $this;
    }

    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    public function getProductUnit(): ?ProductUnit
    {
        return $this->productUnit;
    }

    public function getReferencedUnitPrecisions(): Collection
    {
        return $this->referencedUnitPrecisions;
    }

    /**
     * @param iterable<ProductUnitPrecision> $productUnitPrecisions
     * @return self
     */
    public function setReferencedUnitPrecisions(iterable $productUnitPrecisions): self
    {
        $this->referencedUnitPrecisions->clear();
        foreach ($productUnitPrecisions as $unitPrecision) {
            $this->referencedUnitPrecisions->add($unitPrecision);
        }

        return $this;
    }

    public function addReferencedUnitPrecision(ProductUnitPrecision $productUnitPrecision): self
    {
        if (!$this->referencedUnitPrecisions->contains($productUnitPrecision)) {
            $this->referencedUnitPrecisions->add($productUnitPrecision);
        }

        return $this;
    }

    public function removeReferencedUnitPrecision(ProductUnitPrecision $productUnitPrecision): self
    {
        if ($this->referencedUnitPrecisions->contains($productUnitPrecision)) {
            $this->referencedUnitPrecisions->removeElement($productUnitPrecision);
        }

        return $this;
    }
}
