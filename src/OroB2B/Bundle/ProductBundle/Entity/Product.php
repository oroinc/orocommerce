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
 *          @ORM\Index(name="idx_orob2b_product_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_orob2b_product_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository")
 * @Config(
 *      routeName="orob2b_product_index",
 *      routeView="orob2b_product_view",
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
 */
class Product extends ExtendProduct implements OrganizationAwareInterface
{
    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED = 'enabled';

    const INVENTORY_STATUS_IN_STOCK = 'in_stock';
    const INVENTORY_STATUS_OUT_OF_STOCK = 'out_of_stock';
    const INVENTORY_STATUS_DISCONTINUED = 'discontinued';

    const VISIBILITY_BY_CONFIG = 'by_config';
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_NOT_VISIBLE = 'not_visible';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
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
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $sku;

    /**
     * @var bool
     *
     * @ORM\Column(name="has_variants", type="boolean", nullable=false, options={"default"=false})
     */
    protected $variants = false;

    /**
     * @var array
     *
     * @ORM\Column(name="variant_fields", type="array", nullable=true)
     */
    protected $variantFields;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
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
     */
    protected $organization;

    /**
     * @var Collection|ProductUnitPrecision[]
     *
     * @ORM\OneToMany(targetEntity="ProductUnitPrecision", mappedBy="product", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $unitPrecisions;

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
     */
    protected $descriptions;

    /**
     * @var Collection|ProductVariantLink[]
     *
     * @ORM\OneToMany(targetEntity="ProductVariantLink", mappedBy="product", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $variantLinks;

    /**
     * @var ProductVariantLink
     *
     * @ORM\OneToOne(targetEntity="ProductVariantLink", mappedBy="variant", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $parentProductVariantLink;

    public function __construct()
    {
        parent::__construct();

        $this->unitPrecisions = new ArrayCollection();
        $this->names          = new ArrayCollection();
        $this->descriptions   = new ArrayCollection();
        $this->variantLinks       = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return (string)$this->getDefaultName()->getString();
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
    public function hasVariants()
    {
        return $this->variants;
    }

    /**
     * @param bool $variants
     * @return Product
     */
    public function setVariants($variants)
    {
        $this->variants = $variants;

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
        $existingUnitPrecision = $this->getUnitPrecision($unitPrecision->getUnit()->getCode());

        if ($existingUnitPrecision) {
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
     * @return LocalizedFallbackValue
     * @throws \LogicException
     */
    public function getDefaultName()
    {
        $names = $this->names->filter(function (LocalizedFallbackValue $name) {
            return null === $name->getLocale();
        });

        if ($names->count() !== 1) {
            throw new \LogicException('There must be only one default name');
        }

        return $names->first();
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

        if ($descriptions->count() !== 1) {
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
     */
    public function addVariantLink(ProductVariantLink $variantLink)
    {
        $this->variantLinks->add($variantLink);
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
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->unitPrecisions = new ArrayCollection();
            $this->names = new ArrayCollection();
            $this->descriptions = new ArrayCollection();
            $this->variantLinks = new ArrayCollection();
        }
    }
}
