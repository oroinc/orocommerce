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
 * @ORM\Table(name="orob2b_tax_value")
 * @ORM\HasLifecycleCallbacks
 */
class TaxValue implements CreatedAtAwareInterface, UpdatedAtAwareInterface
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
     * @ORM\Column(name="total_including_tax", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $totalIncludingTax;

    /**
     * @var float
     *
     * @ORM\Column(name="total_excluding_tax", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $totalExcludingTax;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_including_tax", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingIncludingTax;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_excluding_tax", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingExcludingTax;

    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\TaxBundle\Entity\TaxApply")
     * @ORM\JoinTable(
     *      name="orob2b_tax_apply_to_tax_value",
     *      joinColumns={
     *          @ORM\JoinColumn(name="tax_value_id", referencedColumnName="id", onDelete="CASCADE")
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
 * @var float
 *
 * @ORM\Column(name="total_tax_amount", type="float")
 * @ConfigField(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
    protected $totalTaxAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_tax_amount", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingTaxAmount;

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
     * Set totalIncludingTax
     *
     * @param float $totalIncludingTax
     *
     * @return $this
     */
    public function setTotalIncludingTax($totalIncludingTax)
    {
        $this->totalIncludingTax = $totalIncludingTax;

        return $this;
    }

    /**
     * Get totalIncludingTax
     *
     * @return float
     */
    public function getTotalIncludingTax()
    {
        return $this->totalIncludingTax;
    }

    /**
     * Set totalExcludingTax
     *
     * @param float $totalExcludingTax
     *
     * @return $this
     */
    public function setTotalExcludingTax($totalExcludingTax)
    {
        $this->totalExcludingTax = $totalExcludingTax;

        return $this;
    }

    /**
     * Get totalExcludingTax
     *
     * @return float
     */
    public function getTotalExcludingTax()
    {
        return $this->totalExcludingTax;
    }

    /**
     * Set shippingIncludingTax
     *
     * @param float $shippingIncludingTax
     *
     * @return $this
     */
    public function setShippingIncludingTax($shippingIncludingTax)
    {
        $this->shippingIncludingTax = $shippingIncludingTax;

        return $this;
    }

    /**
     * Get shippingIncludingTax
     *
     * @return float
     */
    public function getShippingIncludingTax()
    {
        return $this->shippingIncludingTax;
    }

    /**
     * Set shippingExcludingTax
     *
     * @param float $shippingExcludingTax
     *
     * @return $this
     */
    public function setShippingExcludingTax($shippingExcludingTax)
    {
        $this->shippingExcludingTax = $shippingExcludingTax;

        return $this;
    }

    /**
     * Get shippingExcludingTax
     *
     * @return float
     */
    public function getShippingExcludingTax()
    {
        return $this->shippingExcludingTax;
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
     * Get entityClass
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
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
     * Get appliedTaxes
     *
     * @return TaxApply[]|Collection
     */
    public function getAppliedTaxes()
    {
        return $this->appliedTaxes;
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
     * @param float $totalTaxAmount
     * @return $this
     */
    public function setTotalTaxAmount($totalTaxAmount)
    {
        $this->totalTaxAmount = $totalTaxAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalTaxAmount()
    {
        return $this->totalTaxAmount;
    }

    /**
     * @param float $shippingTaxAmount
     * @return $this
     */
    public function setShippingTaxAmount($shippingTaxAmount)
    {
        $this->shippingTaxAmount = $shippingTaxAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getShippingTaxAmount()
    {
        return $this->shippingTaxAmount;
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
