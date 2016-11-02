<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendAccountUserAwareTrait;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrderBundle\Model\DiscountAwareInterface;
use Oro\Bundle\OrderBundle\Model\ExtendOrder;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="oro_order",indexes={@ORM\Index(name="oro_order_created_at_index", columns={"created_at"})})
 * @ORM\Entity
 * @Config(
 *      routeName="oro_order_index",
 *      routeView="oro_order_view",
 *      routeCreate="oro_order_create",
 *      routeUpdate="oro_order_update",
 *      routeCommerceName="oro_order_frontend_index",
 *      routeCommerceView="oro_order_frontend_view",
 *      routeCommerceCreate="oro_order_frontend_create",
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
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="orders"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Order extends ExtendOrder implements
    OrganizationAwareInterface,
    EmailHolderInterface,
    AccountOwnerAwareInterface,
    LineItemsAwareInterface,
    ShippingAwareInterface,
    CurrencyAwareInterface,
    DiscountAwareInterface,
    SubtotalAwareInterface
{
    use AuditableUserAwareTrait;
    use AuditableFrontendAccountUserAwareTrait;
    use DatesAwareTrait;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=255, unique=true, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $identifier;

    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OrderAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $billingAddress;

    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OrderAddress", cascade={"persist"})
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
     * @var string
     *
     * @ORM\Column(name="customer_notes", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $customerNotes;

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
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $currency;

    /**
     * @var float
     *
     * @ORM\Column(name="subtotal", type="money", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $subtotal;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="money", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $total;

    /**
     * @var PaymentTerm
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentTerm")
     * @ORM\JoinColumn(name="payment_term_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $paymentTerm;

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
     * @var Collection|OrderLineItem[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\OrderBundle\Entity\OrderLineItem",
     *      mappedBy="order", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $lineItems;

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
     * @var Price
     */
    protected $estimatedShippingCost;

    /**
     * @var Price
     */
    protected $overriddenShippingCost;

    /**
     * @var string
     *
     * @ORM\Column(name="source_entity_class", type="string", length=255, nullable=true)
     */
    protected $sourceEntityClass;

    /**
     * @var int
     *
     * @ORM\Column(name="source_entity_id", type="integer", nullable=true )
     */
    protected $sourceEntityId;

    /**
     * @var string
     *
     * @ORM\Column(name="source_entity_identifier", type="string", length=255, nullable=true)
     */
    protected $sourceEntityIdentifier;

    /**
     * @var float
     *
     * @ORM\Column(name="total_discounts_amount", type="money", nullable=true)
     */
    protected $totalDiscountsAmount;

    /**
     * @var Price
     */
    protected $totalDiscounts;

    /**
     * @var Collection|OrderDiscount[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\OrderBundle\Entity\OrderDiscount",
     *      mappedBy="order", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $discounts;

    /**
     * @var Collection|OrderShippingTracking[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\OrderBundle\Entity\OrderShippingTracking",
     *      mappedBy="order", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingTrackings;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->lineItems = new ArrayCollection();
        $this->discounts = new ArrayCollection();
        $this->shippingTrackings = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->identifier;
    }

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
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return Order
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return OrderAddress|null
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param OrderAddress|null $billingAddress
     * @return Order
     */
    public function setBillingAddress(OrderAddress $billingAddress = null)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return OrderAddress|null
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param OrderAddress|null $shippingAddress
     * @return Order
     */
    public function setShippingAddress(OrderAddress $shippingAddress = null)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
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
     * Set poNumber
     *
     * @param string $poNumber
     *
     * @return Order
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
     * Set customerNotes
     *
     * @param string $customerNotes
     *
     * @return Order
     */
    public function setCustomerNotes($customerNotes)
    {
        $this->customerNotes = $customerNotes;

        return $this;
    }

    /**
     * Get customerNotes
     *
     * @return string
     */
    public function getCustomerNotes()
    {
        return $this->customerNotes;
    }

    /**
     * Set shipUntil
     *
     * @param \DateTime $shipUntil
     *
     * @return Order
     */
    public function setShipUntil($shipUntil)
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
     * Set currency
     *
     * @param string $currency
     *
     * @return Order
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
     * Set subtotal
     *
     * @param float $subtotal
     *
     * @return Order
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * Set total
     *
     * @param float $total
     *
     * @return Order
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set paymentTerm
     *
     * @param PaymentTerm|null $paymentTerm
     *
     * @return Order
     */
    public function setPaymentTerm(PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;

        return $this;
    }

    /**
     * Get paymentTerm
     *
     * @return PaymentTerm|null
     */
    public function getPaymentTerm()
    {
        return $this->paymentTerm;
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    public function hasLineItem(OrderLineItem $lineItem)
    {
        return $this->lineItems->contains($lineItem);
    }

    /**
     * Add line item
     *
     * @param OrderLineItem $lineItem
     * @return Order
     */
    public function addLineItem(OrderLineItem $lineItem)
    {
        if (!$this->hasLineItem($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setOrder($this);
        }

        return $this;
    }

    /**
     * Remove line item
     *
     * @param OrderLineItem $lineItem
     * @return Order
     */
    public function removeLineItem(OrderLineItem $lineItem)
    {
        if ($this->hasLineItem($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }

        return $this;
    }

    /**
     * @param Collection|OrderLineItem[] $lineItems
     * @return Order
     */
    public function setLineItems(Collection $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            $lineItem->setOrder($this);
        }

        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * Get orderProducts
     *
     * @return Collection|OrderLineItem[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        if (null !== $this->getAccountUser()) {
            return $this->getAccountUser()->getEmail();
        }

        return '';
    }

    /**
     * @param Website $website
     * @return Order
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

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
     * @return Price|null
     */
    public function getShippingCost()
    {
        return $this->overriddenShippingCostAmount ? $this->overriddenShippingCost : $this->estimatedShippingCost;
    }

    /**
     * @return Price|null
     */
    public function getEstimatedShippingCost()
    {
        return $this->estimatedShippingCost;
    }

    /**
     * @param Price $shippingCost
     * @return Order
     */
    public function setEstimatedShippingCost(Price $shippingCost = null)
    {
        $this->estimatedShippingCost = $shippingCost;

        $this->updateEstimatedShippingCost();

        return $this;
    }

    /**
     * @return Price|null
     */
    public function getOverriddenShippingCost()
    {
        return $this->overriddenShippingCost;
    }

    /**
     * @param Price $shippingCost
     * @return Order
     */
    public function setOverriddenShippingCost(Price $shippingCost = null)
    {
        $this->overriddenShippingCost = $shippingCost;

        $this->updateOverriddenShippingCost();

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->estimatedShippingCostAmount && null !== $this->currency) {
            $this->estimatedShippingCost = Price::create($this->estimatedShippingCostAmount, $this->currency);
        }

        if (null !== $this->overriddenShippingCostAmount && null !== $this->currency) {
            $this->overriddenShippingCost = Price::create($this->overriddenShippingCostAmount, $this->currency);
        }

        if (null !== $this->totalDiscountsAmount && null !== $this->currency) {
            $this->totalDiscounts = Price::create($this->totalDiscountsAmount, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateEstimatedShippingCost()
    {
        $this->estimatedShippingCostAmount =
            $this->estimatedShippingCost ? $this->estimatedShippingCost->getValue() : null;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateOverriddenShippingCost()
    {
        $this->overriddenShippingCostAmount =
            $this->overriddenShippingCost ? $this->overriddenShippingCost->getValue() : null;
    }

    /**
     * Get Source Entity Class
     *
     * @return string
     */
    public function getSourceEntityClass()
    {
        return $this->sourceEntityClass;
    }

    /**
     * Set Source Entity Class
     *
     * @param string $sourceEntityClass
     * @return Order
     */
    public function setSourceEntityClass($sourceEntityClass)
    {
        $this->sourceEntityClass = $sourceEntityClass;

        return $this;
    }

    /**
     * Get Source Entity Id
     *
     * @return string
     */
    public function getSourceEntityId()
    {
        return $this->sourceEntityId;
    }

    /**
     * Set Source Entity Id
     *
     * @param integer $sourceEntityId
     * @return Order
     */
    public function setSourceEntityId($sourceEntityId)
    {
        $this->sourceEntityId = (int)$sourceEntityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceEntityIdentifier()
    {
        return $this->sourceEntityIdentifier;
    }

    /**
     * @param string|null $sourceEntityIdentifier
     * @return Order
     */
    public function setSourceEntityIdentifier($sourceEntityIdentifier = null)
    {
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;

        return $this;
    }

    /**
     * Get total discounts
     *
     * @return Price|null
     */
    public function getTotalDiscounts()
    {
        return $this->totalDiscounts;
    }

    /**
     * Set total discounts
     *
     * @param Price $totalDiscounts
     * @return Order
     */
    public function setTotalDiscounts(Price $totalDiscounts = null)
    {
        $this->totalDiscounts = $totalDiscounts;

        $this->updateTotalDiscounts();

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTotalDiscounts()
    {
        $this->totalDiscountsAmount = $this->totalDiscounts ? $this->totalDiscounts->getValue() : null;
    }

    /**
     * @param OrderDiscount $discount
     * @return bool
     */
    public function hasDiscount(OrderDiscount $discount)
    {
        return $this->discounts->contains($discount);
    }

    /**
     * Add discount
     *
     * @param OrderDiscount $discount
     * @return Order
     */
    public function addDiscount(OrderDiscount $discount)
    {
        if (!$this->hasDiscount($discount)) {
            $this->discounts[] = $discount;
            $discount->setOrder($this);
        }

        return $this;
    }

    /**
     * Remove discount
     *
     * @param OrderDiscount $discount
     * @return Order
     */
    public function removeDiscount(OrderDiscount $discount)
    {
        if ($this->hasDiscount($discount)) {
            $this->discounts->removeElement($discount);
        }

        return $this;
    }

    /**
     * Get order discounts
     *
     * @return Collection|OrderDiscount[]
     */
    public function getDiscounts()
    {
        return $this->discounts;
    }

    /**
     * Reset order discounts
     *
     * @return Order
     */
    public function resetDiscounts()
    {
        $this->discounts = new ArrayCollection();

        return $this;
    }

    /**
     * Reset order line items
     *
     * @return Order
     */
    public function resetLineItems()
    {
        $this->lineItems = new ArrayCollection();

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
     * @param string $shippingMethod
     * @return Order
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
     * @return Order
     */
    public function setShippingMethodType($shippingMethodType)
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    /**
     * @return Collection|OrderShippingTracking[]
     */
    public function getShippingTrackings()
    {
        return $this->shippingTrackings;
    }

    /**
     * @param Collection|OrderShippingTracking[] $shippingTrackings
     * @return Order
     */
    public function setShippingTrackings($shippingTrackings)
    {
        $this->shippingTrackings = $shippingTrackings;

        return $this;
    }

    /**
     * @param OrderShippingTracking $shippingTracking
     * @return bool
     */
    public function hasShippingTracking(OrderShippingTracking $shippingTracking)
    {
        return $this->shippingTrackings->contains($shippingTracking);
    }

    /**
     * @param OrderShippingTracking $shippingTracking
     * @return Order
     */
    public function addShippingTracking(OrderShippingTracking $shippingTracking)
    {
        $shippingTracking->setOrder($this);
        if (!$this->hasShippingTracking($shippingTracking)) {
            $this->shippingTrackings->add($shippingTracking);
        }

        return $this;
    }

    /**
     * @param OrderShippingTracking $shippingTracking
     * @return Order
     */
    public function removeShippingTracking(OrderShippingTracking $shippingTracking)
    {
        if ($this->hasShippingTracking($shippingTracking)) {
            $this->shippingTrackings->removeElement($shippingTracking);
        }

        return $this;
    }
}
