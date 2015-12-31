<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_apply")
 * @ORM\HasLifecycleCallbacks
 */
class TaxApply
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Tax
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $tax;

    /**
     * @var int
     *
     * @ORM\Column(type="percent")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $rate;

    /**
     * @var int
     *
     * @ORM\Column(name="tax_amount", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $taxAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="taxable_amount", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $taxableAmount;

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
     * Set rate
     *
     * @param int $rate
     *
     * @return TaxApply
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set taxAmount
     *
     * @param integer $taxAmount
     *
     * @return TaxApply
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * Get taxAmount
     *
     * @return integer
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * Set taxableAmount
     *
     * @param integer $taxableAmount
     *
     * @return TaxApply
     */
    public function setTaxableAmount($taxableAmount)
    {
        $this->taxableAmount = $taxableAmount;

        return $this;
    }

    /**
     * Get taxableAmount
     *
     * @return integer
     */
    public function getTaxableAmount()
    {
        return $this->taxableAmount;
    }

    /**
     * Set tax
     *
     * @param Tax $tax
     *
     * @return TaxApply
     */
    public function setTax(Tax $tax = null)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * Get tax
     *
     * @return Tax
     */
    public function getTax()
    {
        return $this->tax;
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
