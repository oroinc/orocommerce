<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Model\ExtendCheckout;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @todo consider to use OrderAddress or create new address for checkout
 * @todo add disabled steps flags
 * @todo add workflows relations
 * @todo add source document. Try to use associations
 * @see https://github.com/laboro/platform/blob/master/src/Oro/Bundle/EntityExtendBundle/Resources/doc/associations.md
 *
 * @ORM\Table(name="orob2b_checkout")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=50)
 * @Config(
 *      routeName="orob2b_checkout",
 *      routeView="orob2b_checkout",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
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
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Checkout extends ExtendCheckout implements
    OrganizationAwareInterface,
    AccountOwnerAwareInterface,
    DatesAwareInterface
{
    use DatesAwareTrait;

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
     * @ORM\OneToOne(targetEntity="OrderAddress", cascade={"persist"})
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
     * @ORM\OneToOne(targetEntity="OrderAddress", cascade={"persist"})
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
     * @var
     */
    protected $shippingMethod;

    /**
     * @var
     */
    protected $paymentMethod;

    /**
     * @var Order
     */
    protected $order;

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
     * @return Checkout
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
     * @return Checkout
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
     * @return Checkout
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return Checkout
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

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
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param mixed $paymentMethod
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
     * @return boolean
     */
    public function isSaveBillingAddress()
    {
        return $this->saveBillingAddress;
    }

    /**
     * @param boolean $saveBillingAddress
     * @return Checkout
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
     * @return Checkout
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
     * @return Checkout
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
     * @return Checkout
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
     * @return Checkout
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
}
