<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;

/**
 * Represents a product kit item.
 *
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @method ProductKitItemLabel getLabel(Localization $localization = null)
 * @method ProductKitItemLabel getDefaultLabel()
 * @method $this cloneLocalizedFallbackValueAssociations()
 */
#[ORM\Entity(repositoryClass: ProductKitItemRepository::class)]
#[ORM\Table(name: 'oro_product_kit_item')]
#[ORM\HasLifecycleCallbacks]
#[Config]
class ProductKitItem implements DatesAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?int $id = null;

    /**
     * @var Collection<int, ProductKitItemLabel>
     */
    #[ORM\OneToMany(
        mappedBy: 'kitItem',
        targetEntity: ProductKitItemLabel::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: ['importexport' => ['immutable' => true, 'full' => true, 'fallback_field' => 'string']]
    )]
    protected ?Collection $labels = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?int $sortOrder = 0;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'kitItems')]
    #[ORM\JoinColumn(name: 'product_kit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?Product $productKit = null;

    /**
     * @var Collection<int, ProductKitItemProduct>
     */
    #[ORM\OneToMany(
        mappedBy: 'kitItem',
        targetEntity: ProductKitItemProduct::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[OrderBy(['sortOrder' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true, 'full' => true]])]
    protected ?Collection $kitItemProducts = null;

    #[ORM\Column(name: 'optional', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?bool $optional = false;

    #[ORM\Column(name: 'minimum_quantity', type: Types::FLOAT, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?float $minimumQuantity = null;

    #[ORM\Column(name: 'maximum_quantity', type: Types::FLOAT, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?float $maximumQuantity = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'code', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['immutable' => true]])]
    protected ?ProductUnit $productUnit = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->kitItemProducts = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[\Override]
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
            $this->kitItemProducts = new ArrayCollection();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDefaultLabel($value): self
    {
        $this->setDefaultFallbackValue($this->labels, $value, ProductKitItemLabel::class);
        $this->getDefaultLabel()->setKitItem($this);

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
            $label->setKitItem($this);
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
     * @return Collection<ProductKitItemProduct>
     */
    public function getKitItemProducts()
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

    public function getKitItemProduct(Product $product): ?ProductKitItemProduct
    {
        foreach ($this->kitItemProducts as $productKitItemProduct) {
            if ($productKitItemProduct->getProduct() === $product) {
                return $productKitItemProduct;
            }
        }

        return null;
    }

    /**
     * @return Collection<Product>
     */
    public function getProducts(): Collection
    {
        return $this->kitItemProducts
            ->filter(static fn (ProductKitItemProduct $kitItemProduct) => $kitItemProduct->getProduct() !== null)
            ->map(static fn (ProductKitItemProduct $kitItemProduct) => $kitItemProduct->getProduct());
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
}
