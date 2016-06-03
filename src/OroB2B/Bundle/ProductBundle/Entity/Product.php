<?php
namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Model\ExtendProduct;

/**
 * @ORM\Table(
 *      name="orob2b_product",
 *      indexes={
 *          @ORM\Index(name="idx_orob2b_product_sku", columns={"sku"}),
 *          @ORM\Index(name="idx_orob2b_product_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_orob2b_product_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository")
 * @Config(
 *      routeName="orob2b_product_index",
 *      routeView="orob2b_product_view",
 *      routeUpdate="orob2b_product_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
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
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_product_select",
 *              "grid_name"="products-select-grid"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Product extends ExtendProduct implements OrganizationAwareInterface, \JsonSerializable
{
    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED = 'enabled';
    const INVENTORY_STATUS_IN_STOCK = 'in_stock';
    const INVENTORY_STATUS_OUT_OF_STOCK = 'out_of_stock';
    const INVENTORY_STATUS_DISCONTINUED = 'discontinued';
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
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $sku;
    /**
     * @var bool
     *
     * @ORM\Column(name="has_variants", type="boolean", nullable=false, options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=70
     *          }
     *      }
     * )
     */
    protected $hasVariants = false;
    /**
     * @var bool
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
     * @var \DateTime $createdAt
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
     * @ORM\OneToMany(targetEntity="ProductUnitPrecision", mappedBy="product", cascade={"ALL"}, orphanRemoval=true)
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
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_product_name",
     *      joinColumns={
     *          @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40,
     *              "full"=true,
     *              "fallback_field"="string"
     *          }
     *      }
     * )
     */
    protected $names;
    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_product_description",
     *      joinColumns={
     *          @ORM\JoinColumn(name="description_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=60,
     *              "full"=true,
     *              "fallback_field"="text"
     *          }
     *      }
     * )
     */
    protected $descriptions;
    /**
     * @var Collection|ProductVariantLink[]
     *
     * @ORM\OneToMany(targetEntity="ProductVariantLink", mappedBy="parentProduct", cascade={"ALL"}, orphanRemoval=true)
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
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_product_short_desc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="short_description_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=50,
     *              "full"=true,
     *              "fallback_field"="text"
     *          }
     *      }
     * )
     */
    protected $shortDescriptions;
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->unitPrecisions = new ArrayCollection();
        $this->names = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->variantLinks = new ArrayCollection();
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
    public function __toString()
    {
        try {
            if ($this->getDefaultName()) {
                return (string)$this->getDefaultName();
            } else {
                return (string)$this->sku;
            }
        } catch (\LogicException $e) {
            return (string)$this->sku;
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
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }
    /**
     * @return bool
     */
    public function getHasVariants()
    {
        return $this->hasVariants;
    }
    /**
     * @param bool $hasVariants
     * @return Product
     */
    public function setHasVariants($hasVariants)
    {
        $this->hasVariants = $hasVariants;
        return $this;
    }
    /**
     * @return array
     */
    public function getVariantFields()
    {
        return $this->variantFields;
    }
    /**
     * @param array $variantFields
     * @return Product
     */
    public function setVariantFields(array $variantFields)
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
     * @return Product
     */
    public function setCreatedAt($createdAt)
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
     * @return Product
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
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
     * @return Product
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;
        return $this;
    }
    /**
     * @param OrganizationInterface $organization
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
     * Add unitPrecisions
     *
     * @param ProductUnitPrecision $unitPrecision
     * @return Product
     */
    public function addUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        $productUnit = $unitPrecision->getUnit();
        if ($productUnit && $existingUnitPrecision = $this->getUnitPrecision($productUnit->getCode())) {
            $existingUnitPrecision->setPrecision($unitPrecision->getPrecision());
        } else {
            $unitPrecision->setProduct($this);
            $this->unitPrecisions->add($unitPrecision);
        }
        return $this;
    }
    /**
     * Remove unitPrecisions
     *
     * @param ProductUnitPrecision $unitPrecisions
     * @return Product
     */
    public function removeUnitPrecision(ProductUnitPrecision $unitPrecisions)
    {
        if ($this->unitPrecisions->contains($unitPrecisions)) {
            $this->unitPrecisions->removeElement($unitPrecisions);
        }
        return $this;
    }
    /**
     * Get unitPrecisions
     *
     * @return Collection|ProductUnitPrecision[]
     */
    public function getUnitPrecisions()
    {
        return $this->unitPrecisions;
    }
    /**
     * Get unitPrecisions by unit code
     *
     * @param string $unitCode
     * @return ProductUnitPrecision
     */
    public function getUnitPrecision($unitCode)
    {
        $result = null;
        foreach ($this->unitPrecisions as $unitPrecision) {
            if ($unitPrecision->getUnit()->getCode() == $unitCode) {
                $result = $unitPrecision;
                break;
            }
        }
        return $result;
    }
    /**
     * Get available unit codes
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
     * @return null|LocalizedFallbackValue
     * @throws \LogicException
     */
    public function getDefaultName()
    {
        $names = $this->names->filter(function (LocalizedFallbackValue $name) {
            return null === $name->getLocale();
        });
        if ($names->count() > 1) {
            throw new \LogicException('There must be only one default name');
        } elseif ($names->count() === 1) {
            return $names->first();
        }
        return null;
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
     * @return LocalizedFallbackValue
     * @throws \LogicException
     */
    public function getDefaultDescription()
    {
        $descriptions = $this->descriptions->filter(function (LocalizedFallbackValue $description) {
            return null === $description->getLocale();
        });
        if ($descriptions->count() > 1) {
            throw new \LogicException('There must be only one default description');
        }
        return $descriptions->first();
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
     * @return $this
     */
    public function addVariantLink(ProductVariantLink $variantLink)
    {
        $variantLink->setParentProduct($this);
        if (!$this->variantLinks->contains($variantLink)) {
            $this->variantLinks->add($variantLink);
        }
        return $this;
    }
    /**
     * @param ProductVariantLink $variantLink
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
     * @return LocalizedFallbackValue
     * @throws \LogicException
     */
    public function getDefaultShortDescription()
    {
        $shortDescriptions = $this->shortDescriptions->filter(function (LocalizedFallbackValue $shortDescription) {
            return null === $shortDescription->getLocale();
        });
        if ($shortDescriptions->count() > 1) {
            throw new \LogicException('There must be only one default short description');
        }
        return $shortDescriptions->first();
    }
    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        if (false === $this->hasVariants) {
            // Clear variantLinks in OroB2B\Bundle\ProductBundle\EventListener\ProductHandlerListener
            $this->variantFields = [];
        }
    }
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->unitPrecisions = new ArrayCollection();
            $this->names = new ArrayCollection();
            $this->descriptions = new ArrayCollection();
            $this->shortDescriptions = new ArrayCollection();
            $this->variantLinks = new ArrayCollection();
            $this->variantFields = [];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'product_units' => $this->getAvailableUnitCodes(),
        ];
    }
    /**
     * @param ProductUnitPrecision|null $primaryUnitPrecision
     * @return Product
     */
    public function setPrimaryUnitPrecision($primaryUnitPrecision)
    {
        $this->primaryUnitPrecision = $primaryUnitPrecision;
        if ($primaryUnitPrecision) {
            $this->addUnitPrecision($primaryUnitPrecision);
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
     * Add additionalUnitPrecisions
     *
     * @param ProductUnitPrecision $unitPrecision
     * @return Product
     */
    public function addAdditionalUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        $productUnit = $unitPrecision->getUnit();
        $primary = $this->getPrimaryUnitPrecision();
        $primaryUnit = $primary ? $primary->getUnit() : null;
        if ($productUnit == $primaryUnit) {
            return $this;
        }
        $this->addUnitPrecision($unitPrecision);

        return $this;
    }
    /**
     * Remove additionalUnitPrecisions
     *
     * @param ProductUnitPrecision $unitPrecision
     * @return Product
     */
    public function removeAdditionalUnitPrecision(ProductUnitPrecision $unitPrecision)
    {
        $productUnit = $unitPrecision->getUnit();
        $primary = $this->getPrimaryUnitPrecision();
        $primaryUnit = $primary ? $primary->getUnit() : null;
        if ($productUnit == $primaryUnit) {
            return $this;
        }
        $this->removeUnitPrecision($unitPrecision);

        return $this;
    }
    /**
     * Get additionalUnitPrecisions
     *
     * @return Collection|ProductUnitPrecision[]
     */
    public function getAdditionalUnitPrecisions()
    {
        $additionalPrecisions = $this->unitPrecisions->filter(function($precision) {
            return $precision != $this->primaryUnitPrecision;
        });
        return $additionalPrecisions;
    }
}
