<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroSaleBundle_Entity_Quote;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerAwareInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\Listener\QuoteListener;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteRepository;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\ShippingBundle\Method\Configuration\AllowUnlistedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\MethodLockedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\OverriddenCostShippingMethodConfigurationInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Entity holds information about quote.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @method EnumOptionInterface getInternalStatus()
 * @method EnumOptionInterface getCustomerStatus()
 * @mixin OroSaleBundle_Entity_Quote
 */
#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'oro_sale_quote')]
#[ORM\EntityListeners([QuoteListener::class])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_sale_quote_index',
    routeView: 'oro_sale_quote_view',
    routeUpdate: 'oro_sale_quote_update',
    defaultValues: [
        'entity' => [
            'icon' => 'fa-file-text',
            'contact_information' => ['email' => [['fieldName' => 'contactInformation']]]
        ],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id',
            'frontend_owner_type' => 'FRONTEND_USER',
            'frontend_owner_field_name' => 'customerUser',
            'frontend_owner_column_name' => 'customer_user_id',
            'frontend_customer_field_name' => 'customer',
            'frontend_customer_column_name' => 'customer_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'quotes'],
        'dataaudit' => ['auditable' => true]
    ]
)]
class Quote implements
    CustomerOwnerAwareInterface,
    EmailHolderInterface,
    EmailOwnerAwareInterface,
    OrganizationAwareInterface,
    MethodLockedShippingMethodConfigurationInterface,
    AllowUnlistedShippingMethodConfigurationInterface,
    OverriddenCostShippingMethodConfigurationInterface,
    WebsiteAwareInterface,
    ExtendEntityInterface
{
    use AuditableUserAwareTrait;
    use AuditableFrontendCustomerUserAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    const CUSTOMER_STATUS_CODE = 'quote_customer_status';
    const INTERNAL_STATUS_CODE = 'quote_internal_status';

    const INTERNAL_STATUS_DRAFT = 'draft';
    const INTERNAL_STATUS_DELETED = 'deleted';
    const INTERNAL_STATUS_SENT_TO_CUSTOMER = 'sent_to_customer';

    const INTERNAL_STATUSES = [
        'template',
        'open',
        'sent_to_customer',
        'expired',
        'accepted',
        'declined',
        'cancelled',
    ];

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $qid = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'guest_access_id', type: Types::GUID, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $guestAccessId;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Request::class)]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Request $request = null;

    #[ORM\Column(name: 'po_number', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $poNumber = null;

    #[ORM\Column(name: 'ship_until', type: Types::DATE_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?\DateTimeInterface $shipUntil = null;

    #[ORM\Column(name: 'valid_until', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?\DateTimeInterface $validUntil = null;

    /**
     * @var Collection<int, QuoteProduct>
     */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteProduct::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $quoteProducts = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $expired = false;

    #[ORM\Column(name: 'prices_changed', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $pricesChanged = false;

    #[ORM\OneToOne(targetEntity: QuoteAddress::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'shipping_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?QuoteAddress $shippingAddress = null;

    /**
     * @var Collection<int, User>
     **/
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'oro_quote_assigned_users')]
    #[ORM\JoinColumn(name: 'quote_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $assignedUsers = null;

    /**
     * @var Collection<int, CustomerUser>
     **/
    #[ORM\ManyToMany(targetEntity: CustomerUser::class)]
    #[ORM\JoinTable(name: 'oro_quote_assigned_cus_users')]
    #[ORM\JoinColumn(name: 'quote_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'customer_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $assignedCustomerUsers = null;

    #[ORM\Column(name: 'shipping_method', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $shippingMethod = null;

    #[ORM\Column(name: 'shipping_method_type', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $shippingMethodType = null;

    #[ORM\Column(name: 'shipping_method_locked', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $shippingMethodLocked = false;

    #[ORM\Column(name: 'allow_unlisted_shipping_method', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $allowUnlistedShippingMethod = false;

    /**
     * @var float
     */
    #[ORM\Column(name: 'estimated_shipping_cost_amount', type: 'money', nullable: true)]
    protected $estimatedShippingCostAmount;

    /**
     * @var float
     */
    #[ORM\Column(name: 'override_shipping_cost_amount', type: 'money', nullable: true)]
    protected $overriddenShippingCostAmount;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $currency = null;

    /**
     * @var Collection<int, QuoteDemand>
     */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteDemand::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    protected ?Collection $demands = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->quoteProducts = new ArrayCollection();
        $this->assignedUsers = new ArrayCollection();
        $this->assignedCustomerUsers = new ArrayCollection();
        $this->demands = new ArrayCollection();
        $this->generateGuestAccessId();
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

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
     * Set qid
     *
     * @param string $qid
     * @return Quote
     */
    public function setQid($qid)
    {
        $this->qid = $qid;

        return $this;
    }

    /**
     * Get qid
     *
     * @return string
     */
    public function getQid()
    {
        return $this->qid;
    }

    public function setGuestAccessId(?string $guestAccessId): Quote
    {
        $this->guestAccessId = $guestAccessId;

        return $this;
    }

    public function getGuestAccessId(): ?string
    {
        return $this->guestAccessId;
    }

    /**
     * Set validUntil
     *
     * @param \DateTime|null $validUntil
     * @return Quote
     */
    public function setValidUntil(?\DateTime $validUntil = null)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * Add quoteProducts
     *
     * @param QuoteProduct $quoteProduct
     *
     * @return Quote
     */
    public function addQuoteProduct(QuoteProduct $quoteProduct)
    {
        if (!$this->quoteProducts->contains($quoteProduct)) {
            $this->quoteProducts[] = $quoteProduct;
            $quoteProduct->setQuote($this);
        }

        return $this;
    }

    /**
     * Remove quoteProducts
     *
     * @param QuoteProduct $quoteProduct
     *
     * @return Quote
     */
    public function removeQuoteProduct(QuoteProduct $quoteProduct)
    {
        if ($this->quoteProducts->contains($quoteProduct)) {
            $this->quoteProducts->removeElement($quoteProduct);
        }

        return $this;
    }

    /**
     * Get quoteProducts
     *
     * @return Collection|QuoteProduct[]
     */
    public function getQuoteProducts()
    {
        return $this->quoteProducts;
    }

    /**
     * Set request
     *
     * @param Request|null $request
     *
     * @return Quote
     */
    public function setRequest(?Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expired;
    }

    /**
     * @param bool $expired
     *
     * @return Quote
     */
    public function setExpired($expired)
    {
        $this->expired = (bool)$expired;

        return $this;
    }

    public function isPricesChanged(): bool
    {
        return $this->pricesChanged;
    }

    public function setPricesChanged(bool $pricesChanged): Quote
    {
        $this->pricesChanged = (bool)$pricesChanged;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->id;
    }

    public function __clone()
    {
        $this->generateGuestAccessId();
        $this->cloneExtendEntityStorage();
    }

    /**
     * @return string
     */
    #[\Override]
    public function getEmail()
    {
        if (null !== $this->getCustomerUser()) {
            return (string)$this->getCustomerUser()->getEmail();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasOfferVariants()
    {
        foreach ($this->quoteProducts as $quoteProduct) {
            if ($quoteProduct->hasOfferVariants()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set poNumber
     *
     * @param string $poNumber
     *
     * @return Quote
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * Get poNumber
     *
     * @return string
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * Set shipUntil
     *
     * @param \DateTime|null $shipUntil
     *
     * @return Quote
     */
    public function setShipUntil(?\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
    }

    /**
     * Get shipUntil
     *
     * @return \DateTime
     */
    public function getShipUntil()
    {
        return $this->shipUntil;
    }

    /**
     * @return Website
     */
    #[\Override]
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return $this
     */
    #[\Override]
    public function setWebsite(?Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return QuoteAddress|null
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param QuoteAddress|null $shippingAddress
     *
     * @return Quote
     */
    public function setShippingAddress(?QuoteAddress $shippingAddress = null)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAssignedUsers()
    {
        return $this->assignedUsers;
    }

    /**
     * @param User $assignedUser
     * @return $this
     */
    public function addAssignedUser(User $assignedUser)
    {
        if (!$this->assignedUsers->contains($assignedUser)) {
            $this->assignedUsers->add($assignedUser);
        }

        return $this;
    }

    /**
     * @param User $assignedUser
     * @return $this
     */
    public function removeAssignedUser(User $assignedUser)
    {
        if ($this->assignedUsers->contains($assignedUser)) {
            $this->assignedUsers->removeElement($assignedUser);
        }

        return $this;
    }

    /**
     * @return Collection|CustomerUser[]
     */
    public function getAssignedCustomerUsers()
    {
        return $this->assignedCustomerUsers;
    }

    /**
     * @param CustomerUser $assignedCustomerUser
     * @return $this
     */
    public function addAssignedCustomerUser(CustomerUser $assignedCustomerUser)
    {
        if (!$this->assignedCustomerUsers->contains($assignedCustomerUser)) {
            $this->assignedCustomerUsers->add($assignedCustomerUser);
        }

        return $this;
    }

    /**
     * @param CustomerUser $assignedCustomerUser
     * @return $this
     */
    public function removeAssignedCustomerUser(CustomerUser $assignedCustomerUser)
    {
        if ($this->assignedCustomerUsers->contains($assignedCustomerUser)) {
            $this->assignedCustomerUsers->removeElement($assignedCustomerUser);
        }

        return $this;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }

    /**
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getShippingMethodType()
    {
        return $this->shippingMethodType;
    }

    /**
     * @param string $shippingMethodType
     * @return $this
     */
    public function setShippingMethodType($shippingMethodType)
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    /**
     * @return Price|null
     */
    #[\Override]
    public function getShippingCost()
    {
        $amount = $this->estimatedShippingCostAmount;
        if (null !== $this->overriddenShippingCostAmount) {
            $amount = $this->overriddenShippingCostAmount;
        }
        if ($amount && $this->currency) {
            return Price::create($amount, $this->currency);
        }
        return null;
    }

    /**
     * @return Price|null
     */
    public function getEstimatedShippingCost()
    {
        if ($this->estimatedShippingCostAmount && $this->currency) {
            return Price::create($this->estimatedShippingCostAmount, $this->currency);
        }
        return null;
    }

    /**
     * @return float|null
     */
    public function getEstimatedShippingCostAmount()
    {
        return $this->estimatedShippingCostAmount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setEstimatedShippingCostAmount($amount)
    {
        $this->estimatedShippingCostAmount = $amount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getOverriddenShippingCostAmount()
    {
        return $this->overriddenShippingCostAmount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setOverriddenShippingCostAmount($amount)
    {
        $this->overriddenShippingCostAmount = $amount;

        return $this;
    }

    /**
     * Check if quote is available for acceptance.
     *
     * @return bool
     */
    public function isAcceptable()
    {
        $status = $this->getInternalStatus();

        return !$this->isExpired()
            && $status
            && $status->getInternalId() === self::INTERNAL_STATUS_SENT_TO_CUSTOMER
            && (!$this->getValidUntil() || $this->getValidUntil() >= new \DateTime('now', new \DateTimeZone('UTC')));
    }

    public function isAvailableOnFrontend(): bool
    {
        $status = $this->getInternalStatus();
        if (!$status) {
            return true;
        }

        return in_array($status->getInternalId(), self::INTERNAL_STATUSES, true);
    }

    /**
     * @return QuoteDemand[]|ArrayCollection
     */
    public function getDemands()
    {
        return $this->demands;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isAllowUnlistedShippingMethod()
    {
        return $this->allowUnlistedShippingMethod;
    }

    /**
     * @param bool $allowUnlistedShippingMethod
     *
     * @return $this
     */
    public function setAllowUnlistedShippingMethod($allowUnlistedShippingMethod)
    {
        $this->allowUnlistedShippingMethod = $allowUnlistedShippingMethod;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isShippingMethodLocked()
    {
        return $this->shippingMethodLocked;
    }

    /**
     * @param bool $shippingMethodLocked
     *
     * @return $this
     */
    public function setShippingMethodLocked($shippingMethodLocked)
    {
        $this->shippingMethodLocked = $shippingMethodLocked;

        return $this;
    }

    #[\Override]
    public function isOverriddenShippingCost()
    {
        return null !== $this->overriddenShippingCostAmount;
    }

    #[\Override]
    public function getEmailOwner(): EmailOwnerInterface
    {
        return $this->customerUser;
    }

    private function generateGuestAccessId(): void
    {
        $this->setGuestAccessId(UUIDGenerator::v4());
    }
}
