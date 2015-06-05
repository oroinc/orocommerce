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

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Model\ExtendProduct;

/**
 * @ORM\Table(name="orob2b_product")
 * @ORM\Entity
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
 */
class Product extends ExtendProduct implements OrganizationAwareInterface
{
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
     *          }
     *      }
     * )
     */
    protected $sku;

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
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CatalogBundle\Entity\Category", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $category;

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
     * @ORM\OrderBy({"unit" = "ASC"})
     */
    protected $unitPrecisions;

    public function __construct()
    {
        parent::__construct();

        $this->unitPrecisions = new ArrayCollection();
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
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
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
}
