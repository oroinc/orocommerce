<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCatalogBundle_Entity_Category;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototype;
use Oro\Component\Tree\Entity\TreeTrait;
use Symfony\Component\Validator\Constraints\All;

/**
 * Represents product category
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method File getSmallImage()
 * @method Category setSmallImage(File $smallImage)
 * @method File getLargeImage()
 * @method Category setLargeImage(File $largeImage)
 * @method CategoryTitle getTitle(Localization $localization = null)
 * @method CategoryTitle getDefaultTitle()
 * @method CategoryShortDescription getShortDescription(Localization $localization = null)
 * @method CategoryShortDescription getDefaultShortDescription()
 * @method CategoryLongDescription getLongDescription(Localization $localization = null)
 * @method CategoryLongDescription getDefaultLongDescription()
 * @method LocalizedFallbackValue getMetaTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getMetaKeyword(Localization $localization = null)
 * @method Category setProducts(ArrayCollection $value)
 * @method removeProduct(Product $value)
 * @method ArrayCollection getProducts()
 * @method addProduct(Product $value)
 * @method $this cloneLocalizedFallbackValueAssociations()
 * @mixin OroCatalogBundle_Entity_Category
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'oro_catalog_category')]
#[ORM\Index(columns: ['title'], name: 'idx_oro_category_default_title')]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugPrototypes',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'category_id',
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
        joinTable: new ORM\JoinTable(name: 'oro_catalog_cat_slug_prototype')
    ),
    new ORM\AssociationOverride(
        name: 'slugs',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'category_id',
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
        joinTable: new ORM\JoinTable(name: 'oro_catalog_cat_slug')
    )
])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\Tree(type: 'nested')]
#[Config(
    routeName: 'oro_catalog_category_index',
    defaultValues: [
        'entity' => ['icon' => 'fa-folder'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'catalog'],
        'activity' => [
            'show_on_page' => ActivityScope::UPDATE_PAGE
        ],
        'dataaudit' => ['auditable' => true],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'slug' => ['source' => 'titles']
    ]
)]
class Category implements
    SluggableInterface,
    DatesAwareInterface,
    OrganizationAwareInterface,
    DenormalizedPropertyAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use TreeTrait;
    use SluggableTrait;
    use OrganizationAwareTrait;
    use ExtendEntityTrait;

    const MATERIALIZED_PATH_DELIMITER = '_';
    const CATEGORY_PATH_DELIMITER = ' / ';
    const INDEX_DATA_DELIMITER = '|';
    const FIELD_PARENT_CATEGORY = 'parentCategory';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['identity' => true, 'order' => 10]]
    )]
    protected ?int $id = null;

    /**
     * @var Collection<int, CategoryTitle>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryTitle::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 20, 'full' => true, 'fallback_field' => 'string']
        ]
    )]
    protected ?Collection $titles = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'childCategories')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 30]])]
    protected ?Category $parentCategory = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'parentCategory', targetEntity: Category::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['left' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => true]])]
    protected ?Collection $childCategories = null;

    /**
     * @var Collection<int, CategoryShortDescription>
     */
    #[ORM\OneToMany(
        mappedBy: 'category',
        targetEntity: CategoryShortDescription::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 50, 'full' => true, 'fallback_field' => 'text']])]
    protected ?Collection $shortDescriptions = null;

    /**
     * @var Collection<int, CategoryLongDescription>
     */
    #[ORM\OneToMany(
        mappedBy: 'category',
        targetEntity: CategoryLongDescription::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ConfigField(
        defaultValues: [
            'importexport' => ['order' => 60, 'full' => true, 'fallback_field' => 'wysiwyg'],
            'attachment' => ['acl_protected' => false]
        ]
    )]
    protected ?Collection $longDescriptions = null;

    #[ORM\OneToOne(targetEntity: CategoryDefaultProductOptions::class, cascade: ['persist'])]
    #[ORM\JoinColumn(
        name: 'default_product_options_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?CategoryDefaultProductOptions $defaultProductOptions = null;

    #[ORM\Column(name: 'materialized_path', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?string $materializedPath = null;

    /**
     * This is a mirror field for performance reasons only.
     * It mirrors getDefaultTitle()->getString()
     *
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]], mode: 'hidden')]
    protected ?string $denormalizedDefaultTitle = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     *
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[All(constraints: [new UrlSafeSlugPrototype(['allowSlashes' => true])])]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 40, 'full' => true, 'fallback_field' => 'string']])]
    protected ?Collection $slugPrototypes = null;

    #[ORM\Column(name: 'tree_left', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $left = null;

    #[ORM\Column(name: 'tree_level', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $level = null;

    #[ORM\Column(name: 'tree_right', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $right = null;

    #[ORM\Column(name: 'tree_root', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRoot]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $root = null;

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

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 80]])]
    protected ?OrganizationInterface $organization = null;

    /**
     * Property used by {@see \Gedmo\Tree\Entity\Repository\NestedTreeRepository::__call}
     * @var self|null
     */
    public $sibling;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->childCategories = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->longDescriptions = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return integer
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    public function setDefaultTitle($value)
    {
        $this->setDefaultFallbackValue($this->titles, $value, CategoryTitle::class);
        $this->getDefaultTitle()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryTitle[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param CategoryTitle $title
     *
     * @return $this
     */
    public function addTitle(CategoryTitle $title)
    {
        if (!$this->titles->contains($title)) {
            $title->setCategory($this);
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param CategoryTitle $title
     *
     * @return $this
     */
    public function removeTitle(CategoryTitle $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return Category
     */
    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    /**
     * @param Category|null $parentCategory
     *
     * @return $this
     */
    public function setParentCategory(?Category $parentCategory = null)
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getChildCategories()
    {
        return $this->childCategories;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function addChildCategory(Category $category)
    {
        if (!$this->childCategories->contains($category)) {
            $this->childCategories->add($category);
            $category->setParentCategory($this);
        }

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function removeChildCategory(Category $category)
    {
        if ($this->childCategories->contains($category)) {
            $this->childCategories->removeElement($category);
        }

        return $this;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->updateDenormalizedProperties();
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    #[\Override]
    public function updateDenormalizedProperties(): void
    {
        if (!$this->getDefaultTitle()) {
            throw new \RuntimeException('Category has to have a default title');
        }
        $this->denormalizedDefaultTitle = $this->getDefaultTitle()->getString();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getDefaultTitle();
    }

    public function setDefaultShortDescription($value)
    {
        $this->setDefaultFallbackValue($this->shortDescriptions, $value, CategoryShortDescription::class);
        $this->getDefaultShortDescription()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryShortDescription[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param CategoryShortDescription $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(CategoryShortDescription $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $shortDescription->setCategory($this);
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param CategoryShortDescription $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(CategoryShortDescription $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    public function setDefaultLongDescription($value)
    {
        $this->setDefaultFallbackValue($this->longDescriptions, $value, CategoryLongDescription::class);
        $this->getDefaultShortDescription()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryLongDescription[]
     */
    public function getLongDescriptions()
    {
        return $this->longDescriptions;
    }

    /**
     * @param CategoryLongDescription $longDescription
     *
     * @return $this
     */
    public function addLongDescription(CategoryLongDescription $longDescription)
    {
        if (!$this->longDescriptions->contains($longDescription)) {
            $longDescription->setCategory($this);
            $this->longDescriptions->add($longDescription);
        }

        return $this;
    }

    /**
     * @param CategoryLongDescription $longDescription
     *
     * @return $this
     */
    public function removeLongDescription(CategoryLongDescription $longDescription)
    {
        if ($this->longDescriptions->contains($longDescription)) {
            $this->longDescriptions->removeElement($longDescription);
        }

        return $this;
    }

    /**
     * @return CategoryDefaultProductOptions
     */
    public function getDefaultProductOptions()
    {
        return $this->defaultProductOptions;
    }

    /**
     * Set unitPrecision
     *
     * @param CategoryDefaultProductOptions|null $defaultProductOptions
     *
     * @return Category
     */
    public function setDefaultProductOptions(?CategoryDefaultProductOptions $defaultProductOptions = null)
    {
        $this->defaultProductOptions = $defaultProductOptions;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaterializedPath()
    {
        return $this->materializedPath;
    }

    /**
     * @param string $materializedPath
     *
     * @return Category
     */
    public function setMaterializedPath($materializedPath)
    {
        $this->materializedPath = $materializedPath;

        return $this;
    }

    /**
     * This field is read-only, updated automatically prior to persisting
     *
     * @return string
     */
    public function getDenormalizedDefaultTitle()
    {
        return $this->denormalizedDefaultTitle;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->cloneExtendEntityStorage();
            $this->cloneLocalizedFallbackValueAssociations();
        }
    }
}
