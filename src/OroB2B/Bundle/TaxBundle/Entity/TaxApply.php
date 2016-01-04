<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_apply")
 * @ORM\HasLifecycleCallbacks
 */
class TaxApply implements CreatedAtAwareInterface, UpdatedAtAwareInterface
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
     * @var Tax
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $tax;

    /**
     * @var float
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
     * @var float
     *
     * @ORM\Column(name="tax_amount", type="float")
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
     * @ORM\Column(name="taxable_amount", type="float")
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
     * @param float $rate
     *
     * @return $this
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set taxAmount
     *
     * @param float $taxAmount
     *
     * @return $this
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * Get taxAmount
     *
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * Set taxableAmount
     *
     * @param float $taxableAmount
     *
     * @return $this
     */
    public function setTaxableAmount($taxableAmount)
    {
        $this->taxableAmount = $taxableAmount;

        return $this;
    }

    /**
     * Get taxableAmount
     *
     * @return float
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
     * @return $this
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
