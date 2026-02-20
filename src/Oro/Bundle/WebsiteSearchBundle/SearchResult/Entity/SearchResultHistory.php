<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;

/**
 * Stores search results history items.
 *
 * Functional indexes:
 *  - website_search_result_history_term_lower_idx as LOWER("search_term")
 */
#[ORM\Entity(repositoryClass: SearchResultHistoryRepository::class)]
#[ORM\Table(name: 'oro_website_search_result_history')]
#[ORM\Index(columns: ['normalized_search_term_hash'], name: 'website_search_result_history_sterm_hash_idx')]
#[ORM\UniqueConstraint(name: 'website_search_result_history_search_session_unq', columns: ['search_session'])]
#[Config(
    routeName: 'oro_website_search_result_history_index',
    defaultValues: [
        'entity' => ['icon' => 'fa-search'],
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'search']
    ]
)]
class SearchResultHistory implements
    ExtendEntityInterface,
    CreatedAtAwareInterface,
    OrganizationAwareInterface,
    WebsiteAwareInterface,
    CustomerOwnerAwareInterface
{
    use BusinessUnitAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'UUID')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $id;

    #[ORM\Column(name: 'normalized_search_term_hash', type: Types::STRING, length: 32, nullable: false)]
    private ?string $normalizedSearchTermHash = null;

    #[ORM\Column(name: 'result_type', type: Types::STRING, length: 32, nullable: false)]
    private ?string $resultType = null;

    #[ORM\Column(name: 'results_count', type: Types::INTEGER, nullable: false)]
    private ?int $resultsCount = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', nullable: false, onDelete: 'CASCADE')]
    private ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Localization::class)]
    #[ORM\JoinColumn(name: 'localization_id', nullable: true, onDelete: 'SET NULL')]
    private ?Localization $localization = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'customer_user_id', nullable: true, onDelete: 'SET NULL')]
    private ?CustomerUser $customerUser = null;

    #[ORM\Column(name: 'customer_visitor_id', type: Types::INTEGER, nullable: true)]
    private ?int $customerVisitorId = null;

    #[ORM\Column(name: 'search_session', type: Types::STRING, length: 36, nullable: true)]
    private ?string $searchSession = null;

    #[ORM\Column(name: 'search_term', type: Types::STRING, length: 255, nullable: false)]
    private ?string $searchTerm = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

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

    #[\Override]
    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    #[\Override]
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
    public function setCreatedAt(?\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
