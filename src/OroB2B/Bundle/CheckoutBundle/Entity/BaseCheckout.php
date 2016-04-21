<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait;
use Oro\Component\Layout\ContextItemInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_checkout")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="checkout_discriminator", type="string", length=30)
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class BaseCheckout implements
    CheckoutInterface,
    OrganizationAwareInterface,
    AccountOwnerAwareInterface,
    DatesAwareInterface,
    ContextItemInterface,
    ShippingAwareInterface
{
    use DatesAwareTrait;
    use WorkflowAwareTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="checkout_type", type="string", length=30, nullable=true)
     */
    protected $checkoutType = '';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     */
    protected $poNumber;

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
     * @var Price
     */
    protected $shippingCost;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_until", type="date", nullable=true)
     */
    protected $shipUntil;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_notes", type="text", nullable=true)
     */
    protected $customerNotes;

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
     * @return string
     */
    public function getCheckoutType()
    {
        return $this->checkoutType;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return Checkout
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
     * @return Checkout
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
     * @return string
     */
    public function getCustomerNotes()
    {
        return $this->customerNotes;
    }

    /**
     * @param string $customerNotes
     * @return Checkout
     */
    public function setCustomerNotes($customerNotes)
    {
        $this->customerNotes = $customerNotes;

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
     * @return Checkout
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
     * @return Checkout
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return Checkout
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
     * @return Checkout
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

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
     * @return Checkout
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

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
     * @return Checkout
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
     * @return Checkout
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
     * @return Checkout
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
     * Get shipping estimate
     *
     * @return Price|null
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * Set shipping estimate
     *
     * @param Price $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost = null)
    {
        $this->shippingCost = $shippingCost;

        $this->updateShippingEstimate();

        return $this;
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
     * @return Checkout
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->shippingCost = Price::create($this->shippingEstimateAmount, $this->shippingEstimateCurrency);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateShippingEstimate()
    {
        $this->shippingEstimateAmount = $this->shippingCost ? $this->shippingCost->getValue() : null;
        $this->shippingEstimateCurrency = $this->shippingCost ? $this->shippingCost->getCurrency() : null;
    }
}
