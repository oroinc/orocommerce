<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository")
 * @ORM\Table(name="orob2b_tax_rule")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_rule_index",
 *      routeView="orob2b_tax_rule_view",
 *      routeUpdate="orob2b_tax_rule_update"
 * )
 */
class TaxRule
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
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var ProductTaxCode
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode")
     * @ORM\JoinColumn(name="product_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $productTaxCode;

    /**
     * @var AccountTaxCode
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode")
     * @ORM\JoinColumn(name="account_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accountTaxCode;

    /**
     * @var Tax
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $tax;

    /**
     * @var TaxJurisdiction
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction")
     * @ORM\JoinColumn(name="tax_jurisdiction_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $taxJurisdiction;

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
     *
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param ProductTaxCode $productTaxCode
     *
     * @return $this
     */
    public function setProductTaxCode(ProductTaxCode $productTaxCode = null)
    {
        $this->productTaxCode = $productTaxCode;

        return $this;
    }

    /**
     * @return ProductTaxCode
     */
    public function getProductTaxCode()
    {
        return $this->productTaxCode;
    }

    /**
     * @param AccountTaxCode $accountTaxCode
     *
     * @return $this
     */
    public function setAccountTaxCode(AccountTaxCode $accountTaxCode = null)
    {
        $this->accountTaxCode = $accountTaxCode;

        return $this;
    }

    /**
     * @return AccountTaxCode
     */
    public function getAccountTaxCode()
    {
        return $this->accountTaxCode;
    }

    /**
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
     * @return Tax
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param TaxJurisdiction $taxJurisdiction
     *
     * @return $this
     */
    public function setTaxJurisdiction(TaxJurisdiction $taxJurisdiction = null)
    {
        $this->taxJurisdiction = $taxJurisdiction;

        return $this;
    }

    /**
     * @return TaxJurisdiction
     */
    public function getTaxJurisdiction()
    {
        return $this->taxJurisdiction;
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
