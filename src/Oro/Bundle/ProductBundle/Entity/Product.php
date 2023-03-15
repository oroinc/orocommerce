<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

/**
 * Product entity class.
 *
 * @ORM\Table(
 *      name="oro_product",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uidx_oro_product_sku_organization",
 *          columns={"sku", "organization_id"})
 *      },
 *      indexes={
 *          @ORM\Index(name="idx_oro_product_sku", columns={"sku"}),
 *          @ORM\Index(name="idx_oro_product_sku_uppercase", columns={"sku_uppercase"}),
 *          @ORM\Index(name="idx_oro_product_default_name", columns={"name"}),
 *          @ORM\Index(name="idx_oro_product_default_uppercase", columns={"name_uppercase"}),
 *          @ORM\Index(name="idx_oro_product_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_oro_product_updated_at", columns={"updated_at"}),
 *          @ORM\Index(name="idx_oro_product_status", columns={"status"}),
 *          @ORM\Index(
 *              name="idx_oro_product_created_at_id_organization",
 *              columns={"created_at", "id", "organization_id"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_updated_at_id_organization",
 *              columns={"updated_at", "id", "organization_id"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_sku_id_organization",
 *              columns={"sku", "id", "organization_id"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_status_id_organization",
 *              columns={"status", "id", "organization_id"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_featured",
 *              columns={"is_featured"},
 *              options={"where": "(is_featured = true)"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_id_updated_at",
 *              columns={"id", "updated_at"}
 *          ),
 *          @ORM\Index(
 *              name="idx_oro_product_new_arrival",
 *              columns={"is_new_arrival"},
 *              options={"where": "(is_new_arrival = true)"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository")
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="slugPrototypes",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_product_slug_prototype",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      ),
 *     @ORM\AssociationOverride(
 *          name="slugs",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_product_slug",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="oro_product_index",
 *      routeView="oro_product_view",
 *      routeUpdate="oro_product_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="catalog"
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\ProductBundle\Form\Type\ProductSelectType",
 *              "grid_name"="products-select-grid"
 *          },
 *          "attribute"={
 *              "has_attributes"=true
 *          },
 *          "slug"={
 *              "source"="names"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @method AbstractEnumValue getInventoryStatus()
 * @method Product setInventoryStatus(AbstractEnumValue $enumId)
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
 */
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

    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED = 'enabled';

    const INVENTORY_STATUS_IN_STOCK = 'in_stock';
    const INVENTORY_STATUS_OUT_OF_STOCK = 'out_of_stock';
    const INVENTORY_STATUS_DISCONTINUED = 'discontinued';

    const TYPE_SIMPLE = 'simple';
    const TYPE_CONFIGURABLE = 'configurable';
    const TYPE_KIT = 'kit';

    /**
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
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          },
     *          "frontend"={
     *              "use_in_export"=true
     *          }
     *      }
     * )
     */
    protected $sku;

    /**
     * @var string
     *
     * @ORM\Column(name="sku_uppercase", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *     mode="hidden"
     * )
     */
    protected $skuUppercase;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=16, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     *  )
     */
    protected $status = self::STATUS_DISABLED;

    /**
     * @var array
     *
     * @ORM\Column(name="variant_fields", type="array", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=80,
     *              "process_as_scalar"=true
     *          }
     *      }
     * )
     */
    protected $variantFields = [];

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
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

    /**
     * @var bool
     */
    protected $updatedAtSet;

    /**
     * @var BusinessUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @var Collection|ProductUnitPrecision[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductUnitPrecision",
     *     mappedBy="product",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=30,
     *              "full"=true
     *          }
     *      }
     * )
     */
    protected $unitPrecisions;

    /**
     * @var ProductUnitPrecision
     *
     * @ORM\OneToOne(targetEntity="ProductUnitPrecision", cascade={"persist"})
     * @ORM\JoinColumn(name="primary_unit_precision_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=25,
     *              "full"=true
     *          }
     *      }
     * )
     */
    protected $primaryUnitPrecision;

    /**
     * @var Collection|ProductName[]
     *
     * @ORM\OneToMany(targetEntity="ProductName", mappedBy="product", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40,
     *              "full"=true,
     *              "fallback_field"="string"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          },
     *          "frontend"={
     *              "use_in_export"=true
     *          }
     *      }
     * )
     */
    protected $names;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\OneToMany(
     *      targetEntity="ProductDescription",
     *      mappedBy="product",
     *      cascade={"ALL"},
     *      orphanRemoval=true,
     *      fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=60,
     *              "full"=true,
     *              "fallback_field"="wysiwyg"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          },
     *          "attachment"={
     *              "acl_protected"=false
     *          }
     *      }
     * )
     */
    protected $descriptions;

    /**
     * @var Collection|ProductVariantLink[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductVariantLink",
     *     mappedBy="parentProduct",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=90,
     *              "full"=true,
     *          }
     *      }
     * )
     */
    protected $variantLinks;

    /**
     * @var Collection|ProductVariantLink[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductVariantLink",
     *     mappedBy="product",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *               "excluded"=true
     *          }
     *      }
     * )
     */
    protected $parentVariantLinks;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\OneToMany(
     *      targetEntity="ProductShortDescription",
     *      mappedBy="product",
     *      cascade={"ALL"},
     *      orphanRemoval=true,
     *      fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=50,
     *              "full"=true,
     *              "fallback_field"="text"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          }
     *      }
     * )
     */
    protected $shortDescriptions;

    /**
     * @var Collection|ProductImage[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ProductBundle\Entity\ProductImage",
     *     mappedBy="product",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *               "excluded"=true
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          }
     *      }
     * )
     */
    protected $images;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=32, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     *  )
     */
    protected $type = self::TYPE_SIMPLE;

    /**
     * @var AttributeFamily
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily")
     * @ORM\JoinColumn(name="attribute_family_id", referencedColumnName="id", onDelete="RESTRICT")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $attributeFamily;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_featured", type="boolean", options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "attribute"={
     *              "is_attribute"=true,
     *              "visible"=false
     *          }
     *      }
     *  )
     */
    protected $featured = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_new_arrival", type="boolean", options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "attribute"={
     *              "is_attribute"=true,
     *              "visible"=false
     *          }
     *      }
     *  )
     */
    protected $newArrival = false;

    /**
     * @var Brand
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Brand")
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "attribute"={
     *              "is_attribute"=true,
     *              "visible"=true
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $brand;

    /**
     * This is a mirror field for performance reasons only.
     * It mirrors getDefaultName()->getString().
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *      mode="hidden"
     * )
     */
    protected $denormalizedDefaultName;

    /**
     * @var string
     *
     * @ORM\Column(name="name_uppercase", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *      mode="hidden"
     * )
     */
    protected $denormalizedDefaultNameUppercase;

    /**
     * @var Collection<ProductKitItem>|null
     *
     * @ORM\OneToMany(
     *     targetEntity="ProductKitItem",
     *     mappedBy="productKit",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @OrderBy({"sortOrder"="ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *               "excluded"=true
     *          }
     *      }
     * )
     */
    protected ?Collection $kitItems = null;

    /**
     * {@inheritdoc}
     */
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return Product
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return Product
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
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
     * @param OrganizationInterface $organization
     *
     * @return Product
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    /**
     * Pre update event handler.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();

        if (!$this->isConfigurable()) {
            // Clear variantLinks in Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener
            $this->variantFields = [];
        }
    }

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
    public function setAttributeFamily(AttributeFamily $attributeFamily)
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * @return AttributeFamily
     */
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
