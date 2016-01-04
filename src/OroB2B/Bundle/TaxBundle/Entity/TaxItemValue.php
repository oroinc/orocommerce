<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_item_value")
 * @ORM\HasLifecycleCallbacks
 */
class TaxItemValue implements CreatedAtAwareInterface, UpdatedAtAwareInterface
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="unit_price_including_tax", type="float")
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
     * @var float
     *
     * @ORM\Column(name="unit_price_excluding_tax", type="float")
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
     * @var float
     *
     * @ORM\Column(name="unit_price_tax_amount", type="float")
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
     * @var float
     *
     * @ORM\Column(name="unit_price_adjustment", type="float")
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
     * @var float
     *
     * @ORM\Column(name="row_total_including_tax", type="float")
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
     * @var float
     *
     * @ORM\Column(name="row_total_excluding_tax", type="float")
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
     * @var float
     *
     * @ORM\Column(name="row_total_tax_amount", type="float")
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
     * @var float
     *
     * @ORM\Column(name="row_total_adjustment", type="float")
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
     * @param float $unitPriceIncludingTax
     *
     * @return $this
     */
    public function setUnitPriceIncludingTax($unitPriceIncludingTax)
    {
        $this->unitPriceIncludingTax = $unitPriceIncludingTax;

        return $this;
    }

    /**
     * Get unitPriceIncludingTax
     *
     * @return float
     */
    public function getUnitPriceIncludingTax()
    {
        return $this->unitPriceIncludingTax;
    }

    /**
     * Set unitPriceExcludingTax
     *
     * @param float $unitPriceExcludingTax
     *
     * @return $this
     */
    public function setUnitPriceExcludingTax($unitPriceExcludingTax)
    {
        $this->unitPriceExcludingTax = $unitPriceExcludingTax;

        return $this;
    }

    /**
     * Get unitPriceExcludingTax
     *
     * @return float
     */
    public function getUnitPriceExcludingTax()
    {
        return $this->unitPriceExcludingTax;
    }

    /**
     * Set unitPriceTaxAmount
     *
     * @param float $unitPriceTaxAmount
     *
     * @return $this
     */
    public function setUnitPriceTaxAmount($unitPriceTaxAmount)
    {
        $this->unitPriceTaxAmount = $unitPriceTaxAmount;

        return $this;
    }

    /**
     * Get unitPriceTaxAmount
     *
     * @return float
     */
    public function getUnitPriceTaxAmount()
    {
        return $this->unitPriceTaxAmount;
    }

    /**
     * Set unitPriceAdjustment
     *
     * @param float $unitPriceAdjustment
     *
     * @return $this
     */
    public function setUnitPriceAdjustment($unitPriceAdjustment)
    {
        $this->unitPriceAdjustment = $unitPriceAdjustment;

        return $this;
    }

    /**
     * Get unitPriceAdjustment
     *
     * @return float
     */
    public function getUnitPriceAdjustment()
    {
        return $this->unitPriceAdjustment;
    }

    /**
     * Set rowTotalIncludingTax
     *
     * @param float $rowTotalIncludingTax
     *
     * @return $this
     */
    public function setRowTotalIncludingTax($rowTotalIncludingTax)
    {
        $this->rowTotalIncludingTax = $rowTotalIncludingTax;

        return $this;
    }

    /**
     * Get rowTotalIncludingTax
     *
     * @return float
     */
    public function getRowTotalIncludingTax()
    {
        return $this->rowTotalIncludingTax;
    }

    /**
     * Set rowTotalExcludingTax
     *
     * @param float $rowTotalExcludingTax
     *
     * @return $this
     */
    public function setRowTotalExcludingTax($rowTotalExcludingTax)
    {
        $this->rowTotalExcludingTax = $rowTotalExcludingTax;

        return $this;
    }

    /**
     * Get rowTotalExcludingTax
     *
     * @return float
     */
    public function getRowTotalExcludingTax()
    {
        return $this->rowTotalExcludingTax;
    }

    /**
     * Set rowTotalTaxAmount
     *
     * @param float $rowTotalTaxAmount
     *
     * @return $this
     */
    public function setRowTotalTaxAmount($rowTotalTaxAmount)
    {
        $this->rowTotalTaxAmount = $rowTotalTaxAmount;

        return $this;
    }

    /**
     * Get rowTotalTaxAmount
     *
     * @return float
     */
    public function getRowTotalTaxAmount()
    {
        return $this->rowTotalTaxAmount;
    }

    /**
     * Set rowTotalAdjustment
     *
     * @param float $rowTotalAdjustment
     *
     * @return $this
     */
    public function setRowTotalAdjustment($rowTotalAdjustment)
    {
        $this->rowTotalAdjustment = $rowTotalAdjustment;

        return $this;
    }

    /**
     * Get rowTotalAdjustment
     *
     * @return float
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return TaxApply[]|Collection
     */
    public function getAppliedTaxes()
    {
        return $this->appliedTaxes;
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
