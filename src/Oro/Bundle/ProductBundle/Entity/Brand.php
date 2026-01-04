<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroProductBundle_Entity_Brand;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Repository\BrandRepository;
use Oro\Bundle\ProductBundle\Form\Type\BrandSelectType;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

/**
 * Brand entity class.
 *
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method LocalizedFallbackValue getName(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultName()
 * @method setDefaultName(string $value)
 * @method LocalizedFallbackValue getDefaultSlugPrototype()
 * @method setDefaultSlugPrototype(string $value)
 * @method LocalizedFallbackValue getDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultDescription()
 * @method LocalizedFallbackValue getShortDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultShortDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
 * @method $this cloneLocalizedFallbackValueAssociations()
 * @mixin OroProductBundle_Entity_Brand
 */
#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ORM\Table(name: 'oro_brand')]
#[ORM\Index(columns: ['created_at'], name: 'idx_oro_brand_created_at')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_oro_brand_updated_at')]
#[ORM\Index(columns: ['default_title'], name: 'idx_oro_brand_default_title')]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugPrototypes',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'brand_id',
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
        joinTable: new ORM\JoinTable(name: 'oro_brand_slug_prototype')
    ),
    new ORM\AssociationOverride(
        name: 'slugs',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'brand_id',
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
        joinTable: new ORM\JoinTable(name: 'oro_brand_slug')
    )
])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_product_brand_index',
    defaultValues: [
        'entity' => ['icon' => 'fa-briefcase'],
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'form' => ['form_type' => BrandSelectType::class, 'grid_name' => 'brand-select-grid'],
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'slug' => ['source' => 'names']
    ]
)]
class Brand implements
    OrganizationAwareInterface,
    SluggableInterface,
    DatesAwareInterface,
    DenormalizedPropertyAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use SluggableTrait;
    use ExtendEntityTrait;

    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ENABLED = 'enabled';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 16, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 20]])]
    protected ?string $status = self::STATUS_ENABLED;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?BusinessUnit $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?OrganizationInterface $organization = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_brand_name')]
    #[ORM\JoinColumn(name: 'brand_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 40, 'full' => true, 'fallback_field' => 'string'],
            'attribute' => ['is_attribute' => true]
        ]
    )]
    protected ?Collection $names = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_brand_description')]
    #[ORM\JoinColumn(name: 'brand_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['order' => 60, 'full' => true, 'fallback_field' => 'wysiwyg'],
            'attachment' => ['acl_protected' => false],
            'attribute' => ['is_attribute' => true]
        ]
    )]
    protected ?Collection $descriptions = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_brand_short_desc')]
    #[ORM\JoinColumn(name: 'brand_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['order' => 50, 'full' => true, 'fallback_field' => 'text'],
            'attribute' => ['is_attribute' => true]
        ]
    )]
    protected ?Collection $shortDescriptions = null;

    /**
     * This field stores default name localized value for optimisation purposes
     *
     * @var string
     */
    #[ORM\Column(name: 'default_title', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]], mode: 'hidden')]
    protected ?string $defaultTitle = null;

    public function __construct()
    {
        $this->names = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [self::STATUS_ENABLED, self::STATUS_DISABLED];
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        try {
            if ($this->getDefaultName()) {
                return (string)$this->getDefaultName();
            } else {
                return (string)$this->id;
            }
        } catch (\LogicException $e) {
            return (string)$this->id;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Brand
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
     * @return Brand
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;
        return $this;
    }
    /**
     * @param OrganizationInterface|null $organization
     * @return Brand
     */
    #[\Override]
    public function setOrganization(?OrganizationInterface $organization = null)
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
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function addName(LocalizedFallbackValue $name)
    {
        if (!$this->names->contains($name)) {
            $this->names->add($name);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function removeName(LocalizedFallbackValue $name)
    {
        if ($this->names->contains($name)) {
            $this->names->removeElement($name);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function addDescription(LocalizedFallbackValue $description)
    {
        if (!$this->descriptions->contains($description)) {
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function removeDescription(LocalizedFallbackValue $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    #[\Override]
    public function updateDenormalizedProperties(): void
    {
        $this->defaultTitle = $this->getName()->getString();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->names = new ArrayCollection();
            $this->descriptions = new ArrayCollection();
            $this->shortDescriptions = new ArrayCollection();
            $this->slugPrototypes = new ArrayCollection();
            $this->slugs = new ArrayCollection();
            $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);

            $this->cloneExtendEntityStorage();
            $this->cloneLocalizedFallbackValueAssociations();
        }
    }

    /**
     * This field is read-only, updated automatically prior to persisting
     *
     * @return string
     */
    public function getDefaultTitle()
    {
        return $this->defaultTitle;
    }
}
