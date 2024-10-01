<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Extend\Entity\Autocomplete\OroProductBundle_Entity_Product;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

/**
 * Product entity class.
 *
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method EnumOptionInterface getInventoryStatus()
 * @method Product setInventoryStatus(EnumOptionInterface $enumId)
 * @method ProductName getName(Localization $localization = null)
 * @method ProductName getDefaultName()
 * @method LocalizedFallbackValue getDefaultSlugPrototype()
 * @method setDefaultSlugPrototype(string $value)
 * @method ProductDescription getDescription(Localization $localization = null)
 * @method ProductDescription getDefaultDescription()
 * @method ProductShortDescription getShortDescription(Localization $localization = null)
 * @method ProductShortDescription getDefaultShortDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
 * @method EntityFieldFallbackValue getPageTemplate()
 * @method $this setPageTemplate(EntityFieldFallbackValue $pageTemplate)
 * @method $this cloneLocalizedFallbackValueAssociations()
 * @mixin OroProductBundle_Entity_Product
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'oro_product')]
#[ORM\Index(columns: ['sku'], name: 'idx_oro_product_sku')]
#[ORM\Index(columns: ['sku_uppercase'], name: 'idx_oro_product_sku_uppercase')]
#[ORM\Index(columns: ['name'], name: 'idx_oro_product_default_name')]
#[ORM\Index(columns: ['name_uppercase'], name: 'idx_oro_product_default_uppercase')]
#[ORM\Index(columns: ['created_at'], name: 'idx_oro_product_created_at')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_oro_product_updated_at')]
#[ORM\Index(columns: ['status'], name: 'idx_oro_product_status')]
#[ORM\Index(columns: ['created_at', 'id', 'organization_id'], name: 'idx_oro_product_created_at_id_organization')]
#[ORM\Index(columns: ['updated_at', 'id', 'organization_id'], name: 'idx_oro_product_updated_at_id_organization')]
#[ORM\Index(columns: ['sku', 'id', 'organization_id'], name: 'idx_oro_product_sku_id_organization')]
#[ORM\Index(columns: ['status', 'id', 'organization_id'], name: 'idx_oro_product_status_id_organization')]
#[ORM\Index(columns: ['is_featured'], name: 'idx_oro_product_featured', options: ['where' => '(is_featured = true)'])]
#[ORM\Index(columns: ['id', 'updated_at'], name: 'idx_oro_product_id_updated_at')]
#[ORM\Index(
    columns: ['is_new_arrival'],
    name: 'idx_oro_product_new_arrival',
    options: ['where' => '(is_new_arrival = true)']
)]
#[ORM\UniqueConstraint(name: 'uidx_oro_product_sku_organization', columns: ['sku', 'organization_id'])]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugPrototypes',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'product_id',
                referencedColumnName: 'id',
                onDelete: 'CASCADE'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'localized_value_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_product_slug_prototype')
    ),
    new ORM\AssociationOverride(
        name: 'slugs',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'product_id',
                referencedColumnName: 'id',
                onDelete: 'CASCADE'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'slug_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_product_slug')
    )
])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_product_index',
    routeView: 'oro_product_view',
    routeUpdate: 'oro_product_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-briefcase'],
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => true],
        'security' => [
            'type' => 'ACL',
            'group_name' => '',
            'category' => 'catalog',
            'field_acl_supported' => true,
            'field_acl_enabled' => false
        ],
        'form' => ['form_type' => ProductSelectType::class, 'grid_name' => 'products-select-grid'],
        'attribute' => ['has_attributes' => true],
        'slug' => ['source' => 'names']
    ]
)]
class Product implements
    OrganizationAwareInterface,
    AttributeFamilyAwareInterface,
    SluggableInterface,
    DatesAwareInterface,
    DenormalizedPropertyAwareInterface,
    ExtendEntityInterface
{
    use SluggableTrait;
    use ExtendEntityTrait;


    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ENABLED = 'enabled';

    public const INVENTORY_STATUS_ENUM_CODE = 'prod_inventory_status';
    public const INVENTORY_STATUS_IN_STOCK = 'in_stock';
    public const INVENTORY_STATUS_OUT_OF_STOCK = 'out_of_stock';
    public const INVENTORY_STATUS_DISCONTINUED = 'discontinued';

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_CONFIGURABLE = 'configurable';
    public const TYPE_KIT = 'kit';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['identity' => true, 'order' => 10],
            'attribute' => ['is_attribute' => true],
            'frontend' => ['use_in_export' => true],
            'security' => ['permissions' => 'EDIT']
        ]
    )]
    protected ?string $sku = null;

    #[ORM\Column(name: 'sku_uppercase', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]], mode: 'hidden')]
    protected ?string $skuUppercase = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 16, nullable: false)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 20],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?string $status = self::STATUS_DISABLED;

    /**
     * @var array
     */
    #[ORM\Column(name: 'variant_fields', type: Types::ARRAY, nullable: true)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 80, 'process_as_scalar' => true]
        ]
    )]
    protected $variantFields = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?BusinessUnit $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?OrganizationInterface $organization = null;

    /**
     * @var Collection<int, ProductUnitPrecision>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductUnitPrecision::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 30, 'full' => true],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?Collection $unitPrecisions = null;

    #[ORM\OneToOne(targetEntity: ProductUnitPrecision::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'primary_unit_precision_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 25, 'full' => true],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?ProductUnitPrecision $primaryUnitPrecision = null;

    /**
     * @var Collection<int, ProductName>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductName::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 40, 'full' => true, 'fallback_field' => 'string'],
            'attribute' => ['is_attribute' => true],
            'frontend' => ['use_in_export' => true],
            'security' => ['permissions' => 'EDIT']
        ]
    )]
    protected ?Collection $names = null;

    /**
     * @var Collection<int, ProductDescription>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductDescription::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['order' => 60, 'full' => true, 'fallback_field' => 'wysiwyg'],
            'attribute' => ['is_attribute' => true],
            'attachment' => ['acl_protected' => false]
        ]
    )]
    protected ?Collection $descriptions = null;

    /**
     * @var Collection<int, ProductVariantLink>
     */
    #[ORM\OneToMany(
        mappedBy: 'parentProduct',
        targetEntity: ProductVariantLink::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 90, 'full' => true]]
    )]
    protected ?Collection $variantLinks = null;

    /**
     * @var Collection<int, ProductVariantLink>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductVariantLink::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?Collection $parentVariantLinks = null;

    /**
     * @var Collection<int, ProductShortDescription>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductShortDescription::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['order' => 50, 'full' => true, 'fallback_field' => 'text'],
            'attribute' => ['is_attribute' => true]
        ]
    )]
    protected ?Collection $shortDescriptions = null;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductImage::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['excluded' => true],
            'attribute' => ['is_attribute' => true]
        ]
    )]
    protected ?Collection $images = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 32, nullable: false)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 20],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?string $type = self::TYPE_SIMPLE;

    #[ORM\ManyToOne(targetEntity: AttributeFamily::class)]
    #[ORM\JoinColumn(name: 'attribute_family_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => false],
            'importexport' => ['order' => 10],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?AttributeFamily $attributeFamily = null;

    #[ORM\Column(name: 'is_featured', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(
        defaultValues: [
            'attribute' => ['is_attribute' => true, 'visible' => false],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?bool $featured = false;

    #[ORM\Column(name: 'is_new_arrival', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(
        defaultValues: [
            'attribute' => ['is_attribute' => true, 'visible' => false],
            'security' => ['permissions' => 'VIEW;EDIT']
        ]
    )]
    protected ?bool $newArrival = false;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(name: 'brand_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(
        defaultValues: [
            'attribute' => ['is_attribute' => true, 'visible' => true],
            'dataaudit' => ['auditable' => true],
            'importexport' => ['excluded' => true]
        ]
    )]
    protected ?Brand $brand = null;

    /**
     * This is a mirror field for performance reasons only.
     * It mirrors getDefaultName()->getString().
     *
     * @var string
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]], mode: 'hidden')]
    protected ?string $denormalizedDefaultName = null;

    #[ORM\Column(name: 'name_uppercase', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]], mode: 'hidden')]
    protected ?string $denormalizedDefaultNameUppercase = null;

    /**
     * @var Collection<ProductKitItem>|null
     */
    #[ORM\OneToMany(
        mappedBy: 'productKit',
        targetEntity: ProductKitItem::class,
        cascade: ['ALL'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[OrderBy(['sortOrder' => Criteria::ASC])]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['excluded' => false, 'immutable' => true, 'full' => true, 'process_as_scalar' => true]
        ]
    )]
    protected ?Collection $kitItems = null;

    public function __construct()
    {
        $this->unitPrecisions = new ArrayCollection();
        $this->names = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->variantLinks = new ArrayCollection();
        $this->parentVariantLinks = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
        $this->kitItems = new ArrayCollection();
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [self::STATUS_ENABLED, self::STATUS_DISABLED];
    }

    public function isEnabled(): bool
    {
        return $this->getStatus() === self::STATUS_ENABLED;
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [self::TYPE_SIMPLE, self::TYPE_CONFIGURABLE, self::TYPE_KIT];
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        try {
            if ($this->getDefaultName()) {
                return (string) $this->getDefaultName();
            }

            return (string) $this->sku;
        } catch (\LogicException $e) {
            return (string) $this->sku;
        }
    }

    /**
     * @return int
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        $this->skuUppercase = $this->sku
            ? mb_strtoupper($this->sku)
            : $this->sku;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSimple()
    {
        return $this->getType() === self::TYPE_SIMPLE;
    }

    /**
     * @return bool
     */
    public function isConfigurable()
    {
        return $this->getType() === self::TYPE_CONFIGURABLE;
    }

    public function isKit(): bool
    {
        return $this->getType() === self::TYPE_KIT;
    }

    /**
     * @return array
     */
    public function getVariantFields()
    {
        return (array) $this->variantFields;
    }

    /**
     * @param array|null $variantFields
     *
     * @return Product
     */
    public function setVariantFields($variantFields)
    {
        $this->variantFields = $variantFields;

        return $this;
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     *
     * @return Product
     */
    #[\Override]
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     *
     * @return Product
     */
    #[\Override]
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Product
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return BusinessUnit
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit $owningBusinessUnit
     *
     * @return Product
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * @param OrganizationInterface|null $organization
     *
     * @return Product
     */
    #[\Override]
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    #[\Override]
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Add unitPrecisions.
     *
     * @param ProductUnitPrecision $unitPrecision
     *
     * @return Product
     */
    public function addUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $unitPrecision->getUnit();
        if ($productUnit && $existingUnitPrecision = $this->getUnitPrecision($productUnit->getCode())) {
            $existingUnitPrecision
                ->setPrecision($unitPrecision->getPrecision())
                ->setConversionRate($unitPrecision->getConversionRate())
                ->setSell($unitPrecision->isSell())
                ->setProduct($this);
        } else {
            $unitPrecision->setProduct($this);
            $this->unitPrecisions->add($unitPrecision);
        }

        return $this;
    }

    /**
     * Remove unitPrecisions.
     *
     * @param ProductUnitPrecision $unitPrecision
     *
     * @return Product
     */
    public function removeUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        if ($this->unitPrecisions->contains($unitPrecision)) {
            $this->unitPrecisions->removeElement($unitPrecision);
        }

        return $this;
    }

    /**
     * Get unitPrecisions.
     *
     * @return Collection|ProductUnitPrecision[]
     */
    public function getUnitPrecisions()
    {
        return $this->unitPrecisions;
    }

    /**
     * Get unitPrecisions by unit code.
     *
     * @param string $unitCode
     *
     * @return ProductUnitPrecision|null
     */
    public function getUnitPrecision($unitCode)
    {
        foreach ($this->unitPrecisions as $unitPrecision) {
            $unit = $unitPrecision->getUnit();

            if ($unit && $unit->getCode() === $unitCode) {
                return $unitPrecision;
            }
        }

        return null;
    }

    /**
     * Get available unit codes.
     *
     * @return string[]
     */
    public function getAvailableUnitCodes()
    {
        $result = [];

        foreach ($this->unitPrecisions as $unitPrecision) {
            $result[] = $unitPrecision->getUnit()->getCode();
        }

        return $result;
    }

    /**
     * Get available units.
     *
     * @return ProductUnit[] [unit code => ProductUnit, ...]
     */
    public function getAvailableUnits()
    {
        $result = [];
        foreach ($this->unitPrecisions as $unitPrecision) {
            $unit = $unitPrecision->getUnit();
            $result[$unit->getCode()] = $unit;
        }

        return $result;
    }

    /**
     * @return array [unit code => unit precision, ...]
     */
    public function getAvailableUnitsPrecision()
    {
        $result = [];
        foreach ($this->unitPrecisions as $unitPrecision) {
            $result[$unitPrecision->getUnit()->getCode()] = $unitPrecision->getPrecision();
        }

        return $result;
    }

    /**
     * We need to return only precisions with sell=true for frontend.
     *
     * @return array [unit code => unit precision, ...]
     */
    public function getSellUnitsPrecision()
    {
        $result = [];
        foreach ($this->unitPrecisions as $unitPrecision) {
            if ($unitPrecision->isSell()) {
                $result[$unitPrecision->getUnit()->getCode()] = $unitPrecision->getPrecision();
            }
        }

        return $result;
    }

    public function setDefaultName($value)
    {
        $this->setDefaultFallbackValue($this->names, $value, ProductName::class);
        $this->getDefaultName()->setProduct($this);
        $this->updateDenormalizedProperties();

        return $this;
    }

    /**
     * @param array|ProductName[] $names
     *
     * @return $this
     */
    public function setNames(array $names = [])
    {
        $this->names->clear();

        foreach ($names as $name) {
            $this->addName($name);
        }

        return $this;
    }

    /**
     * @return Collection|ProductName[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param ProductName $name
     *
     * @return $this
     */
    public function addName(ProductName $name)
    {
        if (!$this->names->contains($name)) {
            $name->setProduct($this);
            $this->names->add($name);

            if (!$name->getLocalization()) {
                $this->updateDenormalizedProperties();
            }
        }

        return $this;
    }

    /**
     * @param ProductName $name
     *
     * @return $this
     */
    public function removeName(ProductName $name)
    {
        if ($this->names->contains($name)) {
            $this->names->removeElement($name);
        }

        return $this;
    }

    public function setDefaultDescription($value)
    {
        $this->setDefaultFallbackValue($this->descriptions, $value, ProductDescription::class);
        $this->getDefaultDescription()->setProduct($this);

        return $this;
    }

    /**
     * @param array|ProductDescription[] $descriptions
     *
     * @return $this
     */
    public function setDescriptions(array $descriptions = [])
    {
        $this->descriptions->clear();

        foreach ($descriptions as $description) {
            $this->addDescription($description);
        }

        return $this;
    }

    /**
     * @return Collection|ProductDescription[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param ProductDescription $description
     *
     * @return $this
     */
    public function addDescription(ProductDescription $description)
    {
        if (!$this->descriptions->contains($description)) {
            $description->setProduct($this);
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param ProductDescription $description
     *
     * @return $this
     */
    public function removeDescription(ProductDescription $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return Collection|ProductVariantLink[]
     */
    public function getVariantLinks()
    {
        return $this->variantLinks;
    }

    /**
     * @param ProductVariantLink $variantLink
     *
     * @return $this
     */
    public function addVariantLink(ProductVariantLink $variantLink)
    {
        if (!$variantLink->getParentProduct()) {
            $variantLink->setParentProduct($this);
        }

        if (!$this->variantLinks->contains($variantLink)) {
            $this->variantLinks->add($variantLink);
        }

        return $this;
    }

    /**
     * @param ProductVariantLink $variantLink
     *
     * @return $this
     */
    public function removeVariantLink(ProductVariantLink $variantLink)
    {
        if ($this->variantLinks->contains($variantLink)) {
            $this->variantLinks->removeElement($variantLink);
        }

        return $this;
    }

    /**
     * @return Collection|ProductVariantLink[]
     */
    public function getParentVariantLinks()
    {
        return $this->parentVariantLinks;
    }

    public function isVariant(): bool
    {
        return $this->isSimple() && count($this->parentVariantLinks) > 0;
    }

    /**
     * @param ProductVariantLink $parentVariantLink
     *
     * @return $this
     */
    public function addParentVariantLink(ProductVariantLink $parentVariantLink)
    {
        if (!$parentVariantLink->getProduct()) {
            $parentVariantLink->setProduct($this);
        }

        if (!$this->parentVariantLinks->contains($parentVariantLink)) {
            $this->parentVariantLinks->add($parentVariantLink);
        }

        return $this;
    }

    /**
     * @param ProductVariantLink $parentVariantLink
     *
     * @return $this
     */
    public function removeParentVariantLink(ProductVariantLink $parentVariantLink)
    {
        if ($this->parentVariantLinks->contains($parentVariantLink)) {
            $this->parentVariantLinks->removeElement($parentVariantLink);
        }

        return $this;
    }

    /**
     * @return Collection|ProductImage[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param string $type
     *
     * @return ProductImage[]|Collection
     */
    public function getImagesByType($type)
    {
        return $this->getImages()->filter(function (ProductImage $image) use ($type) {
            return $image->hasType($type);
        });
    }

    /**
     * @param ProductImage $image
     *
     * @return $this
     */
    public function addImage(ProductImage $image)
    {
        $image->setProduct($this);

        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    /**
     * @param ProductImage $image
     *
     * @return $this
     */
    public function removeImage(ProductImage $image)
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
        }

        return $this;
    }

    public function setDefaultShortDescription($value)
    {
        $this->setDefaultFallbackValue($this->shortDescriptions, $value, ProductShortDescription::class);
        $this->getDefaultShortDescription()->setProduct($this);

        return $this;
    }

    /**
     * @param array|ProductShortDescription[] $shortDescriptions
     *
     * @return $this
     */
    public function setShortDescriptions(array $shortDescriptions = [])
    {
        $this->shortDescriptions->clear();

        foreach ($shortDescriptions as $shortDescription) {
            $this->addShortDescription($shortDescription);
        }

        return $this;
    }

    /**
     * @return Collection|ProductShortDescription[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param ProductShortDescription $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(ProductShortDescription $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $shortDescription->setProduct($this);
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param ProductShortDescription $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(ProductShortDescription $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Pre persist event handler.
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    /**
     * Pre update event handler.
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();

        if (!$this->isConfigurable()) {
            // Clear variantLinks in Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener
            $this->variantFields = [];
        }
    }

    #[\Override]
    public function updateDenormalizedProperties(): void
    {
        $this->skuUppercase = $this->sku
            ? mb_strtoupper($this->sku)
            : $this->sku;

        if (!$this->getDefaultName()) {
            throw new \RuntimeException('Product has to have a default name');
        }
        $this->denormalizedDefaultName = $this->getDefaultName()->getString();
        $this->denormalizedDefaultNameUppercase = $this->denormalizedDefaultName
            ? mb_strtoupper($this->denormalizedDefaultName)
            : $this->denormalizedDefaultName;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->unitPrecisions = new ArrayCollection();
            $this->names = new ArrayCollection();
            $this->descriptions = new ArrayCollection();
            $this->shortDescriptions = new ArrayCollection();
            $this->images = new ArrayCollection();
            $this->variantLinks = new ArrayCollection();
            $this->parentVariantLinks = new ArrayCollection();
            $this->slugPrototypes = new ArrayCollection();
            $this->slugs = new ArrayCollection();
            $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
            $this->variantFields = [];

            $this->cloneExtendEntityStorage();
            $this->cloneLocalizedFallbackValueAssociations();
        }
    }

    /**
     * @param ProductUnitPrecision|null $primaryUnitPrecision
     *
     * @return Product
     */
    public function setPrimaryUnitPrecision(?ProductUnitPrecision $primaryUnitPrecision)
    {
        if ($primaryUnitPrecision) {
            $primaryUnitPrecision->setConversionRate(1.0)->setSell(true);
            $this->addUnitPrecision($primaryUnitPrecision);
            $this->primaryUnitPrecision = $this->getUnitPrecision($primaryUnitPrecision->getProductUnitCode());
        } else {
            $this->primaryUnitPrecision = $primaryUnitPrecision;
        }

        return $this;
    }

    /**
     * @return ProductUnitPrecision
     */
    public function getPrimaryUnitPrecision()
    {
        return $this->primaryUnitPrecision;
    }

    /**
     * Add additionalUnitPrecisions.
     *
     * @param ProductUnitPrecision $unitPrecision
     *
     * @return Product
     */
    public function addAdditionalUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        $productUnit = $unitPrecision->getUnit();
        $primary = $this->getPrimaryUnitPrecision();
        $primaryUnit = $primary?->getUnit();
        if ($productUnit == $primaryUnit) {
            return $this;
        }
        $this->addUnitPrecision($unitPrecision);

        return $this;
    }

    /**
     * Remove additionalUnitPrecisions.
     *
     * @param ProductUnitPrecision $unitPrecision
     *
     * @return Product
     */
    public function removeAdditionalUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        $productUnit = $unitPrecision->getUnit();
        $primary = $this->getPrimaryUnitPrecision();
        $primaryUnit = $primary?->getUnit();
        if ($productUnit == $primaryUnit) {
            return $this;
        }
        $this->removeUnitPrecision($unitPrecision);

        return $this;
    }

    /**
     * Get additionalUnitPrecisions.
     *
     * @return Collection|ProductUnitPrecision[]
     */
    public function getAdditionalUnitPrecisions()
    {
        $primaryPrecision = $this->getPrimaryUnitPrecision();
        $additionalPrecisions = $this->getUnitPrecisions()
            ->filter(function ($precision) use ($primaryPrecision) {
                return $precision != $primaryPrecision;
            });

        return new ArrayCollection(array_values($additionalPrecisions->toArray()));
    }

    /**
     * @param AttributeFamily $attributeFamily
     *
     * @return $this
     */
    #[\Override]
    public function setAttributeFamily(AttributeFamily $attributeFamily)
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * @return AttributeFamily
     */
    #[\Override]
    public function getAttributeFamily()
    {
        return $this->attributeFamily;
    }

    /**
     * @return bool
     */
    public function getFeatured()
    {
        return $this->featured;
    }

    /**
     * @param bool $featured
     *
     * @return $this
     */
    public function setFeatured($featured)
    {
        $this->featured = (bool) $featured;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNewArrival()
    {
        return $this->newArrival;
    }

    /**
     * @param bool $newArrival
     *
     * @return $this
     */
    public function setNewArrival($newArrival)
    {
        $this->newArrival = (bool) $newArrival;

        return $this;
    }

    /**
     * @return Brand
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param Brand $brand
     *
     * @return $this
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * This field is read-only, updated automatically prior to persisting.
     *
     * @return string
     */
    public function getSkuUppercase()
    {
        return $this->skuUppercase;
    }

    /**
     * This field is read-only, updated automatically prior to persisting.
     *
     * @return string
     */
    public function getDenormalizedDefaultName()
    {
        return $this->denormalizedDefaultName;
    }

    /**
     * This field is read-only, updated automatically prior to persisting.
     *
     * @return string
     */
    public function getDenormalizedDefaultNameUppercase()
    {
        return $this->denormalizedDefaultNameUppercase;
    }

    public function addKitItem(ProductKitItem $productKitItem): self
    {
        if (!$productKitItem->getProductKit()) {
            $productKitItem->setProductKit($this);
        }

        $kitItems = $this->getKitItems();
        if (!$kitItems->contains($productKitItem)) {
            $kitItems->add($productKitItem);
        }

        return $this;
    }

    public function removeKitItem(ProductKitItem $productKitItem): self
    {
        $kitItems = $this->getKitItems();
        if ($kitItems->contains($productKitItem)) {
            $kitItems->removeElement($productKitItem);
        }

        return $this;
    }

    /**
     * @return Collection<ProductKitItem>
     */
    public function getKitItems(): Collection
    {
        if ($this->kitItems === null) {
            $this->kitItems = new ArrayCollection();
        }

        return $this->kitItems;
    }
}
