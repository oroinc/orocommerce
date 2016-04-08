<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait;
use Oro\Component\Layout\ContextItemInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AlternativeCheckoutBundle\Model\ExtendAlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_alternative_checkout")
 * @ORM\Entity(
 *     repositoryClass="OroB2B\Bundle\AlternativeCheckoutBundle\Entity\Repository\AlternativeCheckoutRepository"
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-shopping-cart"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="accountUser",
 *              "frontend_owner_column_name"="account_user_id",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "workflow"={
 *              "active_workflow"="b2b_flow_alternative_checkout"
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class AlternativeCheckout extends ExtendAlternativeCheckout implements
    CheckoutInterface,
    OrganizationAwareInterface,
    AccountOwnerAwareInterface,
    DatesAwareInterface,
    ContextItemInterface,
    LineItemsNotPricedAwareInterface,
    ShippingAwareInterface
{
    const TYPE = 'alternative';

    use DatesAwareTrait;
    use WorkflowAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $billingAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="save_billing_address", type="boolean")
     */
    protected $saveBillingAddress = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="ship_to_billing_address", type="boolean")
     */
    protected $shipToBillingAddress = false;

    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $shippingAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="save_shipping_address", type="boolean")
     */
    protected $saveShippingAddress = true;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     */
    protected $poNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_notes", type="text", nullable=true)
     */
    protected $customerNotes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_until", type="date", nullable=true)
     */
    protected $shipUntil;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $account;

    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var CheckoutSource
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id", nullable=false)
     */
    protected $source;

    /**
     * @var
     */
    protected $shippingMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", nullable=true)
     */
    protected $paymentMethod;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_estimate_amount", type="money", nullable=true)
     */
    protected $shippingEstimateAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_estimate_currency", type="string", nullable=true, length=3)
     */
    protected $shippingEstimateCurrency;

    /**
     * @var bool
     *
     * @ORM\Column(name="allowed", type="boolean")
     */
    protected $allowed = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="allow_request_date", type="datetime", nullable=true)
     */
    protected $allowRequestDate;

    /**
     * @var Price
     */
    protected $shippingCost;

    /**
     * @var string
     *
     * @ORM\Column(name="request_approval_notes", type="text", nullable=true)
     */
    protected $requestApprovalNotes;

    /**
     * @var bool
     *
     * @ORM\Column(name="requested_for_approve", type="boolean")
     */
    protected $requestedForApprove = false;

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return AlternativeCheckout
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser $accountUser
     * @return AlternativeCheckout
     */
    public function setAccountUser(AccountUser $accountUser)
    {
        $this->accountUser = $accountUser;

        if ($accountUser && $accountUser->getAccount()) {
            $this->setAccount($accountUser->getAccount());
        }

        return $this;
    }

    /**
     * @return OrderAddress
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param OrderAddress $billingAddress
     * @return AlternativeCheckout
     */
    public function setBillingAddress(OrderAddress $billingAddress = null)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerNotes()
    {
        return $this->customerNotes;
    }

    /**
     * @param string $customerNotes
     * @return AlternativeCheckout
     */
    public function setCustomerNotes($customerNotes)
    {
        $this->customerNotes = $customerNotes;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AlternativeCheckout
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface $organization
     * @return AlternativeCheckout
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     * @return AlternativeCheckout
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param mixed $paymentMethod
     * @return AlternativeCheckout
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * @param string $poNumber
     * @return AlternativeCheckout
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSaveBillingAddress()
    {
        return $this->saveBillingAddress;
    }

    /**
     * @param boolean $saveBillingAddress
     * @return AlternativeCheckout
     */
    public function setSaveBillingAddress($saveBillingAddress)
    {
        $this->saveBillingAddress = $saveBillingAddress;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSaveShippingAddress()
    {
        return $this->saveShippingAddress;
    }

    /**
     * @param boolean $saveShippingAddress
     * @return AlternativeCheckout
     */
    public function setSaveShippingAddress($saveShippingAddress)
    {
        $this->saveShippingAddress = $saveShippingAddress;

        return $this;
    }

    /**
     * @return OrderAddress
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param OrderAddress $shippingAddress
     * @return AlternativeCheckout
     */
    public function setShippingAddress(OrderAddress $shippingAddress = null)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }

    /**
     * @param mixed $shippingMethod
     * @return AlternativeCheckout
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShipToBillingAddress()
    {
        return $this->shipToBillingAddress;
    }

    /**
     * @param boolean $shipToBillingAddress
     * @return AlternativeCheckout
     */
    public function setShipToBillingAddress($shipToBillingAddress)
    {
        $this->shipToBillingAddress = $shipToBillingAddress;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getShipUntil()
    {
        return $this->shipUntil;
    }

    /**
     * @param \DateTime $shipUntil
     * @return AlternativeCheckout
     */
    public function setShipUntil(\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
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
     * @return AlternativeCheckout
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return object|null
     */
    public function getSourceEntity()
    {
        if ($this->source) {
            return $this->source->getEntity();
        }

        return null;
    }

    /**
     * @return CheckoutSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param CheckoutSource $source
     * @return AlternativeCheckout
     */
    public function setSource(CheckoutSource $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return AlternativeCheckout
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        /** @var LineItemsNotPricedAwareInterface|LineItemsAwareInterface $sourceEntity */
        $sourceEntity = $this->getSourceEntity();
        return $sourceEntity && ($sourceEntity instanceof LineItemsNotPricedAwareInterface
            || $sourceEntity instanceof LineItemsAwareInterface) ? $sourceEntity->getLineItems() : [];
    }

    /**
     * @return Price|null
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * @param Price $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost = null)
    {
        $this->shippingCost = $shippingCost;

        $this->updateShippingCost();

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->shippingEstimateAmount && null !== $this->shippingEstimateCurrency) {
            $this->shippingCost = Price::create($this->shippingEstimateAmount, $this->shippingEstimateCurrency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateShippingCost()
    {
        $this->shippingEstimateAmount = $this->shippingCost ? $this->shippingCost->getValue() : null;
        $this->shippingEstimateCurrency = $this->shippingCost ? $this->shippingCost->getCurrency() : null;
    }

    /**
     * @return string
     */
    public function getRequestApprovalNotes()
    {
        return $this->requestApprovalNotes;
    }

    /**
     * @param string $requestApprovalNotes
     * @return $this
     */
    public function setRequestApprovalNotes($requestApprovalNotes)
    {
        $this->requestApprovalNotes = $requestApprovalNotes;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequestedForApprove()
    {
        return $this->requestedForApprove;
    }

    /**
     * @param boolean $requestedForApprove
     */
    public function setRequestedForApprove($requestedForApprove)
    {
        $this->requestedForApprove = $requestedForApprove;
    }

    /**
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->allowed;
    }

    /**
     * @param boolean $allowed
     */
    public function setAllowed($allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * @return \DateTime
     */
    public function getAllowRequestDate()
    {
        return $this->allowRequestDate;
    }

    /**
     * @param \DateTime $allowRequestDate
     */
    public function setAllowRequestDate($allowRequestDate)
    {
        $this->allowRequestDate = $allowRequestDate;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
