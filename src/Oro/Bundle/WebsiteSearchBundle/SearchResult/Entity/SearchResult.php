<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Model\ExtendSearchResult;

/**
 * Stores search results items.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_website_search_result")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *     defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="search"
 *          }
 *     }
 * )
 */
class SearchResult extends ExtendSearchResult implements OrganizationAwareInterface
{
    use CreatedAtAwareTrait;
    use OrganizationAwareTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="search_term", type="text")
     */
    protected ?string $searchTerm = null;

    /**
     * @ORM\Column(name="result_type", type="string", length=255)
     */
    protected ?string $resultType = null;

    /**
     * @ORM\Column(name="result", type="integer", options={"unsigned"=true})
     */
    protected ?int $result = null;

    /**
     * @ORM\Column(name="result_details", type="text")
     */
    protected ?string $resultDetails = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected ?Website $website = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected ?Localization $localization = null;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $organization;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?CustomerUser $customerUser = null;

    /**
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Customer $customer = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected BusinessUnit $owner;


    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /**
     * @param string|null $searchTerm
     * @return SearchResult
     */
    public function setSearchTerm(?string $searchTerm): SearchResult
    {
        $this->searchTerm = $searchTerm;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getResultType(): ?string
    {
        return $this->resultType;
    }

    /**
     * @param string|null $resultType
     * @return SearchResult
     */
    public function setResultType(?string $resultType): SearchResult
    {
        $this->resultType = $resultType;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getResult(): ?int
    {
        return $this->result;
    }

    /**
     * @param int|null $result
     * @return SearchResult
     */
    public function setResult(?int $result): SearchResult
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getResultDetails(): ?string
    {
        return $this->resultDetails;
    }

    /**
     * @param string|null $resultDetails
     * @return SearchResult
     */
    public function setResultDetails(?string $resultDetails): SearchResult
    {
        $this->resultDetails = $resultDetails;
        return $this;
    }

    /**
     * @return Website|null
     */
    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return SearchResult
     */
    public function setWebsite(?Website $website): SearchResult
    {
        $this->website = $website;
        return $this;
    }

    /**
     * @return Localization|null
     */
    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    /**
     * @param Localization|null $localization
     * @return SearchResult
     */
    public function setLocalization(?Localization $localization): SearchResult
    {
        $this->localization = $localization;
        return $this;
    }

    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return SearchResult
     */
    public function setCustomerUser(?CustomerUser $customerUser): SearchResult
    {
        $this->customerUser = $customerUser;
        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer|null $customer
     * @return SearchResult
     */
    public function setCustomer(?Customer $customer): SearchResult
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return BusinessUnit
     */
    public function getOwner(): BusinessUnit
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit $owner
     * @return SearchResult
     */
    public function setOwner(BusinessUnit $owner): SearchResult
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
