<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCheckoutBundle_Entity_Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
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
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
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
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @mixin OroCheckoutBundle_Entity_Checkout
 */
#[ORM\Entity(repositoryClass: CheckoutRepository::class)]
#[ORM\Table(name: 'oro_checkout')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-shopping-cart'],
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
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'checkout']
    ]
)]
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

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'po_number', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $poNumber = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Website $website = null;

    #[ORM\Column(name: 'shipping_method', type: Types::STRING, nullable: true)]
    protected ?string $shippingMethod = null;

    #[ORM\Column(name: 'shipping_method_type', type: Types::STRING, nullable: true)]
    protected ?string $shippingMethodType = null;

    #[ORM\Column(name: 'payment_method', type: Types::STRING, nullable: true)]
    protected ?string $paymentMethod = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'shipping_estimate_amount', type: 'money', nullable: true)]
    protected $shippingEstimateAmount;

    #[ORM\Column(name: 'shipping_estimate_currency', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $shippingEstimateCurrency = null;

    /**
     * @var Price
     */
    protected $shippingCost;

    #[ORM\Column(name: 'ship_until', type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $shipUntil = null;

    #[ORM\Column(name: 'customer_notes', type: Types::TEXT, nullable: true)]
    protected ?string $customerNotes = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $currency = null;

    #[ORM\OneToOne(targetEntity: CheckoutSource::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'source_id', referencedColumnName: 'id', nullable: false)]
    protected ?CheckoutSource $source = null;

    #[ORM\Column(name: 'deleted', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $deleted = false;

    #[ORM\Column(name: 'completed', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $completed = false;

    /**
     * @var array|CompletedCheckoutData
     */
    #[ORM\Column(name: 'completed_data', type: 'json_array')]
    protected $completedData;

    #[ORM\Column(name: 'line_item_group_shipping_data', type: 'json', nullable: true)]
    #[ConfigField(mode: 'hidden')]
    private ?array $lineItemGroupShippingData = null;

    /**
     * @var Collection<int, CheckoutLineItem>
     **/
    #[ORM\OneToMany(
        mappedBy: 'checkout',
        targetEntity: CheckoutLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    protected ?Collection $lineItems = null;

    /**
     * @var Collection<int, CheckoutSubtotal>
     **/
    #[ORM\OneToMany(
        mappedBy: 'checkout',
        targetEntity: CheckoutSubtotal::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $subtotals = null;

    #[ORM\OneToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(
        name: 'registered_customer_user_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    protected ?CustomerUser $registeredCustomerUser = null;

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
     * @param \DateTime|null $shipUntil
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
     * @param Price|null $shippingCost
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

    public function getLineItemGroupShippingData(): array
    {
        return $this->lineItemGroupShippingData ?? [];
    }

    public function setLineItemGroupShippingData(array $lineItemGroupShippingData): static
    {
        $this->lineItemGroupShippingData = $lineItemGroupShippingData ?: null;

        return $this;
    }

    #[ORM\PostLoad]
    public function postLoad()
    {
        $this->shippingCost = Price::create($this->shippingEstimateAmount, $this->shippingEstimateCurrency);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
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
