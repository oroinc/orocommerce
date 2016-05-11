<?php

namespace OroB2B\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Ownership\AuditableFrontendAccountUserAwareTrait;
use OroB2B\Bundle\OrderBundle\Model\DiscountAwareInterface;
use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\OrderBundle\Model\ExtendOrder;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_order",indexes={@ORM\Index(name="orob2b_order_created_at_index", columns={"created_at"})})
 * @ORM\Entity
 * @Config(
 *      routeName="orob2b_order_index",
 *      routeView="orob2b_order_view",
 *      routeCreate="orob2b_order_create",
 *      routeUpdate="orob2b_order_update",
 *      routeCommerceName="orob2b_order_frontend_index",
 *      routeCommerceView="orob2b_order_frontend_view",
 *      routeCommerceCreate="orob2b_order_frontend_create",
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
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
    DiscountAwareInterface
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm")
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
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
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderLineItem",
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
     * @var float
     *
     * @ORM\Column(name="shipping_cost_amount", type="money", nullable=true)
     */
    protected $shippingCostAmount;

    /**
     * @var Price
     */
    protected $shippingCost;

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
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderDiscount",
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
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->lineItems = new ArrayCollection();
        $this->discounts = new ArrayCollection();
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
     * Get subtotal
     *
     * @return float
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
     * @return $this
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
     * @return $this
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
     * Get shipping cost
     *
     * @return Price|null
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * Set shipping cost
     *
     * @param Price $shippingCost
     * @return $this
     */
    public function setShippingCost(Price $shippingCost = null)
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
        if (null !== $this->shippingCostAmount && null !== $this->currency) {
            $this->shippingCost = Price::create($this->shippingCostAmount, $this->currency);
        }

        if (null !== $this->totalDiscountsAmount && null !== $this->currency) {
            $this->totalDiscounts = Price::create($this->totalDiscountsAmount, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateShippingCost()
    {
        $this->shippingCostAmount = $this->shippingCost ? $this->shippingCost->getValue() : null;
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
     *
     * @return $this
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
     *
     * @return $this
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
     *
     * @return $this
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
     * @return $this
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
     *
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
     *
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
     *
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
}
