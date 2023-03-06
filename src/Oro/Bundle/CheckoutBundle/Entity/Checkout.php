<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\FrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Checkout entity
 *
 * @ORM\Table(name="oro_checkout")
 * @ORM\Entity(repositoryClass="Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository")
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-shopping-cart"
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
 *              "category"="checkout"
 *          }
 *      }
 * )
 */
class Checkout implements
    CheckoutInterface,
    ProductLineItemsHolderInterface,
    OrganizationAwareInterface,
    CustomerOwnerAwareInterface,
    CustomerVisitorOwnerAwareInterface,
    DatesAwareInterface,
    ShippingAwareInterface,
    PaymentMethodAwareInterface,
    WebsiteAwareInterface,
    CurrencyAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use FrontendCustomerUserAwareTrait;
    use CheckoutAddressesTrait;
    use ExtendEntityTrait;

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
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method", type="string", nullable=true)
     */
    protected $shippingMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method_type", type="string", nullable=true)
     */
    protected $shippingMethodType;

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
     * @ORM\OneToOne(targetEntity="Oro\Bundle\CheckoutBundle\Entity\CheckoutSource", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id", nullable=false)
     */
    protected $source;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="completed", type="boolean", options={"default"=false})
     */
    protected $completed = false;

    /**
     * @var array|CompletedCheckoutData
     *
     * @ORM\Column(name="completed_data", type="json_array")
     */
    protected $completedData;

    /**
     * @var Collection|CheckoutLineItem[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem",
     *      mappedBy="checkout",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     **/
    protected $lineItems;

    /**
     * @var Collection|CheckoutSubtotal[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal",
     *      mappedBy="checkout",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $subtotals;

    /**
     * @var CustomerUser
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(
     *     name="registered_customer_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL"
     * )
     */
    protected $registeredCustomerUser;

    public function __construct()
    {
        $this->completedData = new CompletedCheckoutData();
        $this->lineItems = new ArrayCollection();
        $this->subtotals = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
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
     * {@inheritDoc}
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * {@inheritDoc}
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
     * @return string
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
     * @param string $shippingMethodType
     * @return $this
     */
    public function setShippingMethodType($shippingMethodType)
    {
        $this->shippingMethodType = $shippingMethodType;

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
     * {@inheritDoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritDoc}
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntity()
    {
        if ($this->source) {
            return $this->source->getEntity();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function setSource(CheckoutSource $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * {@inheritDoc}
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
    public function setShippingCost(Price $shippingCost = null)
    {
        $this->shippingCost = $shippingCost;

        $this->updateShippingEstimate();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param bool $deleted
     *
     * @return $this
     */
    public function setDeleted($deleted)
    {
        $this->deleted = (bool)$deleted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $completed
     *
     * @return $this
     */
    public function setCompleted($completed)
    {
        $this->completed = (bool)$completed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    /**
     * @return CompletedCheckoutData
     */
    public function getCompletedData()
    {
        if (!$this->completedData instanceof CompletedCheckoutData) {
            $this->completedData = CompletedCheckoutData::jsonDeserialize($this->completedData);
        }

        return $this->completedData;
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

    /**
     * @param CheckoutLineItem $item
     *
     * @return $this
     */
    public function addLineItem(CheckoutLineItem $item)
    {
        if (!$this->lineItems->contains($item)) {
            $item->setCheckout($this);
            $this->lineItems->add($item);
        }

        return $this;
    }

    /**
     * @param CheckoutLineItem $item
     *
     * @return $this
     */
    public function removeLineItem(CheckoutLineItem $item)
    {
        $this->lineItems->removeElement($item);

        return $this;
    }

    /**
     * @return Collection|CheckoutLineItem[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param Collection $lineItems
     *
     * @return $this
     */
    public function setLineItems(Collection $lineItems)
    {
        $this->lineItems->clear();

        foreach ($lineItems as $lineItem) {
            $this->addLineItem($lineItem);
        }

        return $this;
    }

    /**
     * @return Collection|CheckoutSubtotal[]
     */
    public function getSubtotals()
    {
        return $this->subtotals;
    }

    /**
     * @return CustomerVisitor|null
     */
    public function getVisitor()
    {
        $sourceEntity = $this->getSourceEntity();

        if ($sourceEntity instanceof CustomerVisitorOwnerAwareInterface) {
            return $sourceEntity->getVisitor();
        }

        return null;
    }

    /**
     * @return CustomerUser|null
     */
    public function getRegisteredCustomerUser()
    {
        return $this->registeredCustomerUser;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return $this
     */
    public function setRegisteredCustomerUser($customerUser)
    {
        $this->registeredCustomerUser = $customerUser;

        return $this;
    }
}
