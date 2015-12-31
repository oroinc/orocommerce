<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_item_value")
 * @ORM\HasLifecycleCallbacks
 */
class TaxItemValue
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="unit_price_including_tax", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $unitPriceIncludingTax;

    /**
     * @var int
     *
     * @ORM\Column(name="unit_price_excluding_tax", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $unitPriceExcludingTax;

    /**
     * @var int
     *
     * @ORM\Column(name="unit_price_tax_amount", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $unitPriceTaxAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="unit_price_adjustment", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $unitPriceAdjustment;

    /**
     * @var int
     *
     * @ORM\Column(name="row_total_including_tax", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $rowTotalIncludingTax;

    /**
     * @var int
     *
     * @ORM\Column(name="row_total_excluding_tax", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $rowTotalExcludingTax;

    /**
     * @var int
     *
     * @ORM\Column(name="row_total_tax_amount", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $rowTotalTaxAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="row_total_adjustment", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $rowTotalAdjustment;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $entityClass;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $address;

    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\TaxBundle\Entity\TaxApply")
     * @ORM\JoinTable(
     *      name="orob2b_tax_app_to_tax_i_value",
     *      joinColumns={
     *          @ORM\JoinColumn(name="tax_item_value_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="tax_apply_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var TaxApply[]|Collection
     */
    protected $appliedTaxes;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     *
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     *
     * @var \DateTime
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->appliedTaxes = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set unitPriceIncludingTax
     *
     * @param integer $unitPriceIncludingTax
     *
     * @return TaxItemValue
     */
    public function setUnitPriceIncludingTax($unitPriceIncludingTax)
    {
        $this->unitPriceIncludingTax = $unitPriceIncludingTax;

        return $this;
    }

    /**
     * Get unitPriceIncludingTax
     *
     * @return integer
     */
    public function getUnitPriceIncludingTax()
    {
        return $this->unitPriceIncludingTax;
    }

    /**
     * Set unitPriceExcludingTax
     *
     * @param integer $unitPriceExcludingTax
     *
     * @return TaxItemValue
     */
    public function setUnitPriceExcludingTax($unitPriceExcludingTax)
    {
        $this->unitPriceExcludingTax = $unitPriceExcludingTax;

        return $this;
    }

    /**
     * Get unitPriceExcludingTax
     *
     * @return integer
     */
    public function getUnitPriceExcludingTax()
    {
        return $this->unitPriceExcludingTax;
    }

    /**
     * Set unitPriceTaxAmount
     *
     * @param integer $unitPriceTaxAmount
     *
     * @return TaxItemValue
     */
    public function setUnitPriceTaxAmount($unitPriceTaxAmount)
    {
        $this->unitPriceTaxAmount = $unitPriceTaxAmount;

        return $this;
    }

    /**
     * Get unitPriceTaxAmount
     *
     * @return integer
     */
    public function getUnitPriceTaxAmount()
    {
        return $this->unitPriceTaxAmount;
    }

    /**
     * Set unitPriceAdjustment
     *
     * @param integer $unitPriceAdjustment
     *
     * @return TaxItemValue
     */
    public function setUnitPriceAdjustment($unitPriceAdjustment)
    {
        $this->unitPriceAdjustment = $unitPriceAdjustment;

        return $this;
    }

    /**
     * Get unitPriceAdjustment
     *
     * @return integer
     */
    public function getUnitPriceAdjustment()
    {
        return $this->unitPriceAdjustment;
    }

    /**
     * Set rowTotalIncludingTax
     *
     * @param integer $rowTotalIncludingTax
     *
     * @return TaxItemValue
     */
    public function setRowTotalIncludingTax($rowTotalIncludingTax)
    {
        $this->rowTotalIncludingTax = $rowTotalIncludingTax;

        return $this;
    }

    /**
     * Get rowTotalIncludingTax
     *
     * @return integer
     */
    public function getRowTotalIncludingTax()
    {
        return $this->rowTotalIncludingTax;
    }

    /**
     * Set rowTotalExcludingTax
     *
     * @param integer $rowTotalExcludingTax
     *
     * @return TaxItemValue
     */
    public function setRowTotalExcludingTax($rowTotalExcludingTax)
    {
        $this->rowTotalExcludingTax = $rowTotalExcludingTax;

        return $this;
    }

    /**
     * Get rowTotalExcludingTax
     *
     * @return integer
     */
    public function getRowTotalExcludingTax()
    {
        return $this->rowTotalExcludingTax;
    }

    /**
     * Set rowTotalTaxAmount
     *
     * @param integer $rowTotalTaxAmount
     *
     * @return TaxItemValue
     */
    public function setRowTotalTaxAmount($rowTotalTaxAmount)
    {
        $this->rowTotalTaxAmount = $rowTotalTaxAmount;

        return $this;
    }

    /**
     * Get rowTotalTaxAmount
     *
     * @return integer
     */
    public function getRowTotalTaxAmount()
    {
        return $this->rowTotalTaxAmount;
    }

    /**
     * Set rowTotalAdjustment
     *
     * @param integer $rowTotalAdjustment
     *
     * @return TaxItemValue
     */
    public function setRowTotalAdjustment($rowTotalAdjustment)
    {
        $this->rowTotalAdjustment = $rowTotalAdjustment;

        return $this;
    }

    /**
     * Get rowTotalAdjustment
     *
     * @return integer
     */
    public function getRowTotalAdjustment()
    {
        return $this->rowTotalAdjustment;
    }

    /**
     * Set entityClass
     *
     * @param string $entityClass
     *
     * @return TaxItemValue
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get entityClass
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     *
     * @return TaxItemValue
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return TaxItemValue
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Add appliedTax
     *
     * @param TaxApply $appliedTax
     *
     * @return TaxItemValue
     */
    public function addAppliedTax(TaxApply $appliedTax)
    {
        if (!$this->appliedTaxes->contains($appliedTax)) {
            $this->appliedTaxes->add($appliedTax);
        }

        return $this;
    }

    /**
     * Remove appliedTax
     *
     * @param TaxApply $appliedTax
     * @return $this
     */
    public function removeAppliedTax(TaxApply $appliedTax)
    {
        if ($this->appliedTaxes->contains($appliedTax)) {
            $this->appliedTaxes->removeElement($appliedTax);
        }

        return $this;
    }

    /**
     * Get appliedTaxes
     *
     * @return Collection
     */
    public function getAppliedTaxes()
    {
        return $this->appliedTaxes;
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
     * @return $this
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
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
