<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_value")
 * @ORM\HasLifecycleCallbacks
 */
class TaxValue
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
     * @ORM\Column(name="total_including_tax", type="integer")
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
     * @var int
     *
     * @ORM\Column(name="total_excluding_tax", type="integer")
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
     * @var int
     *
     * @ORM\Column(name="shipping_including_tax", type="integer")
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
     * @var int
     *
     * @ORM\Column(name="shipping_excluding_tax", type="integer")
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
     * Set totalIncludingTax
     *
     * @param integer $totalIncludingTax
     *
     * @return TaxValue
     */
    public function setTotalIncludingTax($totalIncludingTax)
    {
        $this->totalIncludingTax = $totalIncludingTax;

        return $this;
    }

    /**
     * Get totalIncludingTax
     *
     * @return integer
     */
    public function getTotalIncludingTax()
    {
        return $this->totalIncludingTax;
    }

    /**
     * Set totalExcludingTax
     *
     * @param integer $totalExcludingTax
     *
     * @return TaxValue
     */
    public function setTotalExcludingTax($totalExcludingTax)
    {
        $this->totalExcludingTax = $totalExcludingTax;

        return $this;
    }

    /**
     * Get totalExcludingTax
     *
     * @return integer
     */
    public function getTotalExcludingTax()
    {
        return $this->totalExcludingTax;
    }

    /**
     * Set shippingIncludingTax
     *
     * @param integer $shippingIncludingTax
     *
     * @return TaxValue
     */
    public function setShippingIncludingTax($shippingIncludingTax)
    {
        $this->shippingIncludingTax = $shippingIncludingTax;

        return $this;
    }

    /**
     * Get shippingIncludingTax
     *
     * @return integer
     */
    public function getShippingIncludingTax()
    {
        return $this->shippingIncludingTax;
    }

    /**
     * Set shippingExcludingTax
     *
     * @param integer $shippingExcludingTax
     *
     * @return TaxValue
     */
    public function setShippingExcludingTax($shippingExcludingTax)
    {
        $this->shippingExcludingTax = $shippingExcludingTax;

        return $this;
    }

    /**
     * Get shippingExcludingTax
     *
     * @return integer
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
     * @return TaxValue
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
     * @return TaxValue
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
     * @return TaxValue
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
     * @return TaxValue
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
