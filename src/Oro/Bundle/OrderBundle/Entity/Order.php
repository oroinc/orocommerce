<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrencyHolderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderNameInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Model\DiscountAwareInterface;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Order entity
 *
 * @ORM\Table(name="oro_order",indexes={@ORM\Index(name="oro_order_created_at_index", columns={"created_at"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository")
 * @Config(
 *      routeName="oro_order_index",
 *      routeView="oro_order_view",
 *      routeCreate="oro_order_create",
 *      routeUpdate="oro_order_update",
 *      routeCommerceView="oro_order_frontend_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-usd",
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
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="orders"
 *          },
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
 *
 * @method AbstractEnumValue getInternalStatus()
 * @method $this setInternalStatus(AbstractEnumValue $status)
 */
class Order implements
    OrganizationAwareInterface,
    EmailHolderInterface,
    EmailHolderNameInterface,
    CustomerOwnerAwareInterface,
    LineItemsAwareInterface,
    ShippingAwareInterface,
    CurrencyAwareInterface,
    DiscountAwareInterface,
    SubtotalAwareInterface,
    MultiCurrencyHolderInterface,
    WebsiteAwareInterface,
    CheckoutSourceEntityInterface,
    ProductLineItemsHolderInterface,
    PreConfiguredShippingMethodConfigurationInterface,
    ExtendEntityInterface
{
    use AuditableUserAwareTrait;
    use AuditableFrontendCustomerUserAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    const INTERNAL_STATUS_CODE = 'order_internal_status';

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
     * Changes to this value object wont affect entity change set
     * To change persisted price value you should create and set new Multicurrency
     *
     * @var Multicurrency
     */
    protected $subtotal;

    /**
     * @var float
     *
     * @ORM\Column(name="subtotal_with_discounts", type="money", nullable=true)
     */
    protected $subtotalWithDiscounts;

    /**
     * @var string
     *
     * @ORM\Column(name="subtotal_currency", type="currency", length=3, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="subtotal"
     *      }
     *  }
     * )
     */
    protected $subtotalCurrency;

    /**
     * @var double
     *
     * @ORM\Column(name="subtotal_value", type="money_value", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="Oro\Bundle\FormBundle\Form\Type\OroMoneyType",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0}}},
     *          }
     *      },
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="subtotal",
     *          "virtual_field"="subtotalBaseCurrency"
     *      }
     *  }
     * )
     */
    protected $subtotalValue;

    /**
     * @var float
     *
     * @ORM\Column(name="base_subtotal_value", type="money", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="subtotal"
     *      }
     *  }
     * )
     */
    protected $baseSubtotalValue;

    /**
     * Changes to this value object wont affect entity change set
     * To change persisted price value you should create and set new Multicurrency
     *
     * @var Multicurrency
     */
    protected $total;

    /**
     * @var string
     *
     * @ORM\Column(name="total_currency", type="currency", length=3, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="total"
     *      }
     *  }
     * )
     */
    protected $totalCurrency;

    /**
     * @var double
     *
     * @ORM\Column(name="total_value", type="money_value", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="Oro\Bundle\FormBundle\Form\Type\OroMoneyType",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0}}},
     *          }
     *      },
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="total",
     *          "virtual_field"="totalBaseCurrency"
     *      }
     *  }
     * )
     */
    protected $totalValue;

    /**
     * @var float
     *
     * @ORM\Column(name="base_total_value", type="money", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "multicurrency"={
     *          "target"="total"
     *      }
     *  }
     * )
     */
    protected $baseTotalValue;

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
     * @ORM\OrderBy({"id" = "ASC"})
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
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="subOrders")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Order", mappedBy="parent", orphanRemoval=true, cascade={"all"})
     */
    protected $subOrders;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        $this->discounts = new ArrayCollection();
        $this->shippingTrackings = new ArrayCollection();
        $this->subOrders = new ArrayCollection();
        $this->loadMultiCurrencyFields();
    }

    /**
     * @ORM\PostLoad
     */
    public function loadMultiCurrencyFields()
    {
        $this->subtotal = MultiCurrency::create(
            $this->subtotalValue,
            $this->currency,
            $this->baseSubtotalValue
        );
        $this->total = MultiCurrency::create(
            $this->totalValue,
            $this->currency,
            $this->baseTotalValue
        );
    }

    /**
     * @ORM\PreFlush
     *
     * @return void
     */
    public function updateMultiCurrencyFields()
    {
        $this->fixCurrencyInMultiCurrencyFields();
        $this->updateSubtotal();
        $this->updateTotal();
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
     * {@inheritDoc}
     */
    public function getSourceDocument()
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceDocumentIdentifier()
    {
        return $this->identifier;
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
     * @param \DateTime|null $shipUntil
     *
     * @return Order
     */
    public function setShipUntil(\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
    }

    /**
     * Get shipUntil
     */
    public function getShipUntil(): ?\DateTime
    {
        return $this->shipUntil;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        $this->subtotal->setCurrency($currency);
        $this->total->setCurrency($currency);

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
     * @return float
     */
    public function getBaseSubtotalValue()
    {
        return $this->baseSubtotalValue;
    }

    /**
     * @param float $baseValue
     *
     * @return $this
     */
    public function setBaseSubtotalValue($baseValue)
    {
        $this->baseSubtotalValue = $baseValue;
        $this->subtotal->setBaseCurrencyValue($baseValue);

        return $this;
    }

    /**
     * Set subtotal
     *
     * @param float $value
     *
     * @return $this
     */
    public function setSubtotal($value)
    {
        $this->subtotalValue = $value;
        $this->subtotal->setValue($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return $this->subtotal->getValue();
    }

    /**
     * @param MultiCurrency $subtotal
     *
     * @return $this
     */
    public function setSubtotalObject(MultiCurrency $subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @return MultiCurrency
     */
    public function getSubtotalObject()
    {
        return $this->subtotal;
    }

    /**
     * @return float
     */
    public function getBaseTotalValue()
    {
        return $this->baseTotalValue;
    }

    /**
     * @param $baseValue
     *
     * @return $this
     */
    public function setBaseTotalValue($baseValue)
    {
        $this->baseTotalValue = $baseValue;
        $this->total->setBaseCurrencyValue($baseValue);

        return $this;
    }

    /**
     * Set total
     *
     * @param float $value
     *
     * @return $this
     */
    public function setTotal($value)
    {
        $this->totalValue = $value;
        $this->total->setValue($value);

        return $this;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total->getValue();
    }

    /**
     * @return MultiCurrency
     */
    public function getTotalObject()
    {
        return $this->total;
    }

    /**
     * @param MultiCurrency $total
     *
     * @return $this
     */
    public function setTotalObject(MultiCurrency $total)
    {
        $this->total = $total;

        return $this;
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
     * {@inheritDoc}
     */
    public function getEmail()
    {
        if (null !== $this->getCustomerUser()) {
            return $this->getCustomerUser()->getEmail();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailHolderName()
    {
        if (null !== $this->getCustomerUser()) {
            return implode(' ', [
                $this->getCustomerUser()->getFirstName(),
                $this->getCustomerUser()->getLastName(),
            ]);
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

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
    public function getShippingCost()
    {
        $amount = $this->estimatedShippingCostAmount;
        if ($this->overriddenShippingCostAmount !== null) {
            $amount = $this->overriddenShippingCostAmount;
        }
        if (null !== $amount && $this->currency) {
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
     * @return Order
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
     * @return Order
     */
    public function setOverriddenShippingCostAmount($amount)
    {
        $this->overriddenShippingCostAmount = $amount;

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->totalDiscountsAmount && null !== $this->currency) {
            $this->totalDiscounts = Price::create($this->totalDiscountsAmount, $this->currency);
        }
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
     * {@inheritDoc}
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
        $this->shippingMethod = (string) $shippingMethod;

        return $this;
    }

    /**
     * {@inheritDoc}
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
        $this->shippingMethodType = (string) $shippingMethodType;

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

    protected function fixCurrencyInMultiCurrencyFields()
    {
        $multiCurrencyFields = [$this->total, $this->subtotal];
        /**
         * @var MultiCurrency $multiCurrencyField
         */
        foreach ($multiCurrencyFields as $multiCurrencyField) {
            if ($multiCurrencyField->getCurrency() !== $this->currency) {
                $multiCurrencyField->setCurrency($this->currency);
            }
        }
    }

    /**
     * @param string $subtotalCurrency
     */
    protected function setSubtotalCurrency($subtotalCurrency)
    {
        $this->subtotalCurrency = $subtotalCurrency;
        $this->subtotal->setCurrency($subtotalCurrency);
    }

    /**
     * @param string $totalCurrency
     */
    protected function setTotalCurrency($totalCurrency)
    {
        $this->totalCurrency = $totalCurrency;
        $this->total->setCurrency($totalCurrency);
    }

    protected function updateSubtotal()
    {
        $this->subtotalValue = $this->subtotal->getValue();
        if (null !== $this->subtotalValue) {
            $this->setSubtotalCurrency($this->subtotal->getCurrency());
            $this->setBaseSubtotalValue($this->subtotal->getBaseCurrencyValue());
            return;
        }

        $this->setBaseSubtotalValue(null);
    }

    protected function updateTotal()
    {
        $this->totalValue = $this->total->getValue();
        if (null !== $this->totalValue) {
            $this->setTotalCurrency($this->total->getCurrency());
            $this->setBaseTotalValue($this->total->getBaseCurrencyValue());
            return;
        }

        $this->setBaseTotalValue(null);
    }

    public function getSubtotalWithDiscounts(): ?float
    {
        return $this->subtotalWithDiscounts;
    }

    public function setSubtotalWithDiscounts(?float $subtotalWithDiscounts): void
    {
        $this->subtotalWithDiscounts = $subtotalWithDiscounts;
    }

    /**
     * @return array|Product[]
     */
    public function getProductsFromLineItems()
    {
        $products = [];
        foreach ($this->getLineItems() as $lineItem) {
            if ($lineItem->getProduct()) {
                $products[] = $lineItem->getProduct();
            }
        }

        return $products;
    }

    public function getParent(): ?Order
    {
        return $this->parent;
    }

    public function setParent(?Order $order): self
    {
        $this->parent = $order;

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getSubOrders(): iterable
    {
        return $this->subOrders;
    }

    public function addSubOrder(Order $order): self
    {
        if (!$this->subOrders->contains($order)) {
            $this->subOrders->add($order);
            $order->setParent($this);
        }

        return $this;
    }

    public function removeSubOrder(Order $order): self
    {
        if ($this->subOrders->contains($order)) {
            $this->subOrders->removeElement($order);
            $order->setParent(null);
        }

        return $this;
    }
}
