<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;

/**
 * Entity that represents tax rule
 */
#[ORM\Entity(repositoryClass: TaxRuleRepository::class)]
#[ORM\Table(name: 'oro_tax_rule')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_tax_rule_index',
    routeView: 'oro_tax_rule_view',
    routeUpdate: 'oro_tax_rule_update',
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ]
    ]
)]
class TaxRule implements DatesAwareInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 500]])]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ProductTaxCode::class)]
    #[ORM\JoinColumn(name: 'product_tax_code_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true, 'order' => 200]])]
    protected ?ProductTaxCode $productTaxCode = null;

    #[ORM\ManyToOne(targetEntity: CustomerTaxCode::class)]
    #[ORM\JoinColumn(name: 'customer_tax_code_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true, 'order' => 100]])]
    protected ?CustomerTaxCode $customerTaxCode = null;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    #[ORM\JoinColumn(name: 'tax_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true, 'order' => 400]])]
    protected ?Tax $tax = null;

    #[ORM\ManyToOne(targetEntity: TaxJurisdiction::class)]
    #[ORM\JoinColumn(name: 'tax_jurisdiction_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true, 'order' => 300]])]
    protected ?TaxJurisdiction $taxJurisdiction = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
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
     * @param ProductTaxCode|null $productTaxCode
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
     * @param CustomerTaxCode|null $customerTaxCode
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
     * @param Tax|null $tax
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
     * @param TaxJurisdiction|null $taxJurisdiction
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
    #[\Override]
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return $this
     */
    #[\Override]
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     *
     * @return $this
     */
    #[\Override]
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
    #[\Override]
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
