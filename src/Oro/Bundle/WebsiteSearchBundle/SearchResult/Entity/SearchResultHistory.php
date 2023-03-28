<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Stores search results history items.
 *
 * Functional indexes:
 *  - website_search_result_history_term_lower_idx as LOWER("search_term")
 *
 * @ORM\Entity(
 *     repositoryClass="Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository"
 * )
 * @ORM\Table(
 *     name="oro_website_search_result_history",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="website_search_result_history_search_session_unq", columns={"search_session"})
 *     },
 *     indexes={
 *         @ORM\Index(name="website_search_result_history_sterm_hash_idx", columns={"normalized_search_term_hash"})
 *     }
 * )
 * @Config(
 *     routeName="oro_website_search_result_history_index",
 *     defaultValues={
 *         "entity"={
 *             "icon"="fa-search"
 *         },
 *         "ownership"={
 *             "owner_type"="BUSINESS_UNIT",
 *             "owner_field_name"="owner",
 *             "owner_column_name"="business_unit_owner_id",
 *             "organization_field_name"="organization",
 *             "organization_column_name"="organization_id"
 *         },
 *         "security"={
 *             "type"="ACL",
 *             "group_name"="commerce",
 *             "category"="search"
 *         }
 *     }
 * )
 */
class SearchResultHistory implements
    ExtendEntityInterface,
    CreatedAtAwareInterface,
    OrganizationAwareInterface,
    WebsiteAwareInterface
{
    use BusinessUnitAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
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
     * @ORM\Column(
     *     name="normalized_search_term_hash",
     *     type="string",
     *     length=32,
     *     nullable=false
     * )
     */
    private $normalizedSearchTermHash;

    /**
     * @ORM\Column(
     *     name="result_type",
     *     type="string",
     *     length=32,
     *     nullable=false
     * )
     */
    private $resultType;

    /**
     * @ORM\Column(
     *     name="results_count",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $resultsCount;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website"
     * )
     * @ORM\JoinColumn(
     *     name="website_id",
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    private $website;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization"
     * )
     * @ORM\JoinColumn(
     *     name="localization_id",
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    private $localization;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer"
     * )
     * @ORM\JoinColumn(
     *     name="customer_id",
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    private $customer;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser"
     * )
     * @ORM\JoinColumn(
     *     name="customer_user_id",
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    private $customerUser;

    /**
     * @ORM\Column(
     *     name="customer_visitor_id",
     *     type="integer",
     *     nullable=true
     * )
     */
    private $customerVisitorId;

    /**
     * @ORM\Column(
     *     name="search_session",
     *     type="string",
     *     length=36,
     *     nullable=true
     * )
     */
    private $searchSession;

    /**
     * @ORM\Column(
     *     name="search_term",
     *     type="string",
     *     length=255,
     *     nullable=false
     * )
     */
    private $searchTerm;

    /**
     * @var \DateTime
     *
     * @Doctrine\ORM\Mapping\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNormalizedSearchTermHash(): ?string
    {
        return $this->normalizedSearchTermHash;
    }

    public function setNormalizedSearchTermHash(string $normalizedSearchTermHash): self
    {
        $this->normalizedSearchTermHash = $normalizedSearchTermHash;

        return $this;
    }

    public function getResultType(): ?string
    {
        return $this->resultType;
    }

    public function setResultType(string $resultType): self
    {
        $this->resultType = $resultType;

        return $this;
    }

    public function getResultsCount(): ?int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): self
    {
        $this->resultsCount = $resultsCount;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    public function setLocalization(?Localization $localization): self
    {
        $this->localization = $localization;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(?CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;

        return $this;
    }

    public function getCustomerVisitorId(): ?int
    {
        return $this->customerVisitorId;
    }

    public function setCustomerVisitorId(?int $customerVisitorId): self
    {
        $this->customerVisitorId = $customerVisitorId;

        return $this;
    }

    public function getSearchSession(): ?string
    {
        return $this->searchSession;
    }

    public function setSearchSession(?string $searchSession): self
    {
        $this->searchSession = $searchSession;

        return $this;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): self
    {
        $this->searchTerm = $searchTerm;

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
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
