<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_apply")
 */
class TaxApply implements DatesAwareInterface
{
    use DatesAwareTrait;

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
     */
    protected $rate;

    /**
     * @var float
     *
     * @ORM\Column(name="tax_amount", type="float")
     */
    protected $taxAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="taxable_amount", type="float")
     */
    protected $taxableAmount;

    /**
     * @var TaxValue
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\TaxValue", inversedBy="appliedTaxes")
     * @ORM\JoinColumn(name="tax_value_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $taxValue;

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
     * @param TaxValue $taxValue
     * @return TaxApply
     */
    public function setTaxValue(TaxValue $taxValue)
    {
        $this->taxValue = $taxValue;

        return $this;
    }

    /**
     * @return TaxValue
     */
    public function getTaxValue()
    {
        return $this->taxValue;
    }
}
