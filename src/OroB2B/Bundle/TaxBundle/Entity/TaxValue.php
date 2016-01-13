<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

use OroB2B\Bundle\TaxBundle\Model\Result;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="orob2b_tax_value",
 *     indexes={
 *         @ORM\Index(name="orob2b_tax_value_class_id_idx", columns={"entity_class", "entity_id"})
 *     }
 * )
 */
class TaxValue implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Result
     *
     * @ORM\Column(name="result", type="object")
     */
    protected $result;

    /**
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\TaxBundle\Entity\TaxApply",
     *      mappedBy="taxValue",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     *
     * @var TaxApply[]|Collection
     */
    protected $appliedTaxes;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected $entityClass;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer")
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $address;

    public function __construct()
    {
        $this->appliedTaxes = new ArrayCollection();
        $this->result = new Result();
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
     * @param Result $result
     * @return $this
     */
    public function setResult(Result $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }
}
