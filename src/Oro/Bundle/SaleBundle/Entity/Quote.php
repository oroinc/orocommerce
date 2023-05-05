<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerAwareInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
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
 * @ORM\Table(name="oro_sale_quote")
 * @ORM\Entity(repositoryClass="Oro\Bundle\SaleBundle\Entity\Repository\QuoteRepository")
 * @ORM\EntityListeners({"Oro\Bundle\SaleBundle\Entity\Listener\QuoteListener"})
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_sale_quote_index",
 *      routeView="oro_sale_quote_view",
 *      routeUpdate="oro_sale_quote_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-file-text",
 *              "contact_information"={
 *                  "email"={
 *                      {"fieldName"="contactInformation"}
 *                  }
 *              }
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="customerUser",
 *              "frontend_owner_column_name"="customer_user_id",
 *              "frontend_customer_field_name"="customer",
 *              "frontend_customer_column_name"="customer_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="quotes"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @method AbstractEnumValue getInternalStatus()
 * @method AbstractEnumValue getCustomerStatus()
 */
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

    const FRONTEND_INTERNAL_STATUSES = [
        'template',
        'open',
        'sent_to_customer',
        'expired',
        'accepted',
        'declined',
        'cancelled',
    ];

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $qid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guest_access_id", type="guid", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $guestAccessId;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $website;

    /**
     * @var Request
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RFPBundle\Entity\Request")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $request;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $poNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_until", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shipUntil;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_until", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $validUntil;

    /**
     * @var Collection|QuoteProduct[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProduct", mappedBy="quote", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $quoteProducts;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $expired = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="prices_changed", type="boolean", options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $pricesChanged = false;

    /**
     * @var QuoteAddress
     *
     * @ORM\OneToOne(targetEntity="QuoteAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingAddress;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      name="oro_quote_assigned_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedUsers;

    /**
     * @var Collection|CustomerUser[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinTable(
     *      name="oro_quote_assigned_cus_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedCustomerUsers;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method", type="string", length=255, nullable=true)
     */
    protected $shippingMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method_type", type="string", length=255, nullable=true)
     */
    protected $shippingMethodType;

    /**
     * @ORM\Column(name="shipping_method_locked", type="boolean", options={"default"=false})
     *
     * @var bool
     */
    protected $shippingMethodLocked = false;

    /**
     * @ORM\Column(name="allow_unlisted_shipping_method", type="boolean", options={"default"=false})
     *
     * @var bool
     */
    protected $allowUnlistedShippingMethod = false;

    /**
     * @var float
     *
     * @ORM\Column(name="estimated_shipping_cost_amount", type="money", nullable=true)
     */
    protected $estimatedShippingCostAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="override_shipping_cost_amount", type="money", nullable=true)
     */
    protected $overriddenShippingCostAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true, length=3)
     */
    protected $currency;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\SaleBundle\Entity\QuoteDemand",
     *     mappedBy="quote",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $demands;

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

    /**
     * @param string $guestAccessId
     * @return Quote
     */
    public function setGuestAccessId(?string $guestAccessId): Quote
    {
        $this->guestAccessId = $guestAccessId;

        return $this;
    }

    /**
     * @return string
     */
    public function getGuestAccessId(): ?string
    {
        return $this->guestAccessId;
    }

    /**
     * Set validUntil
     *
     * @param \DateTime $validUntil
     * @return Quote
     */
    public function setValidUntil(\DateTime $validUntil = null)
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
     * @param Request $request
     *
     * @return Quote
     */
    public function setRequest(Request $request = null)
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
     * @param \DateTime $shipUntil
     *
     * @return Quote
     */
    public function setShipUntil(\DateTime $shipUntil = null)
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
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function setWebsite(Website $website = null)
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
    public function setShippingAddress(QuoteAddress $shippingAddress = null)
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
            && $status->getId() === self::INTERNAL_STATUS_SENT_TO_CUSTOMER
            && (!$this->getValidUntil() || $this->getValidUntil() >= new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return bool
     */
    public function isAvailableOnFrontend()
    {
        $status = $this->getInternalStatus();

        return !$status || \in_array($status->getId(), self::FRONTEND_INTERNAL_STATUSES, true);
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

    /**
     * {@inheritdoc}
     */
    public function isOverriddenShippingCost()
    {
        return null !== $this->overriddenShippingCostAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailOwner(): EmailOwnerInterface
    {
        return $this->customerUser;
    }

    private function generateGuestAccessId(): void
    {
        $this->setGuestAccessId(UUIDGenerator::v4());
    }
}
