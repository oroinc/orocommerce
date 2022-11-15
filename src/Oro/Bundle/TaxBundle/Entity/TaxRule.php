<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Entity that represents tax rule
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository")
 * @ORM\Table(name="oro_tax_rule")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_rule_index",
 *      routeView="oro_tax_rule_view",
 *      routeUpdate="oro_tax_rule_update",
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class TaxRule implements DatesAwareInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
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
     *          },
     *          "importexport"={
     *              "order"=500
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var ProductTaxCode
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TaxBundle\Entity\ProductTaxCode")
     * @ORM\JoinColumn(name="product_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true,
     *              "order"=200
     *          }
     *      }
     * )
     */
    protected $productTaxCode;

    /**
     * @var CustomerTaxCode
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TaxBundle\Entity\CustomerTaxCode")
     * @ORM\JoinColumn(name="customer_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true,
     *              "order"=100
     *          }
     *      }
     * )
     */
    protected $customerTaxCode;

    /**
     * @var Tax
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TaxBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true,
     *              "order"=400
     *          }
     *      }
     * )
     */
    protected $tax;

    /**
     * @var TaxJurisdiction
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TaxBundle\Entity\TaxJurisdiction")
     * @ORM\JoinColumn(name="tax_jurisdiction_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true,
     *              "order"=300
     *          }
     *      }
     * )
     */
    protected $taxJurisdiction;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
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
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?Organization $organization = null;

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
     * @param CustomerTaxCode $customerTaxCode
     *
     * @return $this
     */
    public function setCustomerTaxCode(CustomerTaxCode $customerTaxCode = null)
    {
        $this->customerTaxCode = $customerTaxCode;

        return $this;
    }

    /**
     * @return CustomerTaxCode
     */
    public function getCustomerTaxCode()
    {
        return $this->customerTaxCode;
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
    public function setCreatedAt(\DateTime $createdAt = null)
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
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAtSet = false;
        if ($updatedAt !== null) {
            $this->updatedAtSet = true;
        }

        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }
}
