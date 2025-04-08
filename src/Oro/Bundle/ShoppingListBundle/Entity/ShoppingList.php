<?php

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroShoppingListBundle_Entity_ShoppingList;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Shopping List entity
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @method ArrayCollection|CustomerVisitor[] getVisitors()
 * @mixin OroShoppingListBundle_Entity_ShoppingList
 */
#[ORM\Entity(repositoryClass: ShoppingListRepository::class)]
#[ORM\Table(name: 'oro_shopping_list')]
#[ORM\Index(columns: ['created_at'], name: 'oro_shop_lst_created_at_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_shopping_list_index',
    routeView: 'oro_shopping_list_view',
    defaultValues: [
        'entity' => [
            'icon' => 'fa-shopping-cart',
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
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'shopping']
    ]
)]
class ShoppingList implements
    OrganizationAwareInterface,
    LineItemsNotPricedAwareInterface,
    CustomerOwnerAwareInterface,
    CustomerVisitorOwnerAwareInterface,
    WebsiteBasedCurrencyAwareInterface,
    CheckoutSourceEntityInterface,
    \JsonSerializable,
    ProductLineItemsHolderInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use AuditableFrontendCustomerUserAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $label = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Website $website = null;

    /**
     * @var Collection<int, LineItem>
     **/
    #[ORM\OneToMany(mappedBy: 'shoppingList', targetEntity: LineItem::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    protected ?Collection $lineItems = null;

    /**
     * @var bool
     */
    protected $current = false;

    /**
     * @var Collection<int, ShoppingListTotal>
     **/
    #[ORM\OneToMany(
        mappedBy: 'shoppingList',
        targetEntity: ShoppingListTotal::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $totals = null;

    /**
     * Overrides $customerUser property defined in {@see AuditableFrontendCustomerUserAwareTrait} to disable cascade
     * persist.
     */
    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'customer_user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?CustomerUser $customerUser = null;

    /**
     * Overrides $customer property defined in {@see AuditableFrontendCustomerAwareTrait} to disable cascade persist.
     */
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Customer $customer = null;

    /**
     * @var Subtotal
     */
    protected $subtotal;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $currency = null;


    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        $this->totals = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->label;
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
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param LineItem $item
     *
     * @return $this
     */
    public function addLineItem(LineItem $item)
    {
        if (!$this->lineItems->contains($item)) {
            $item->setShoppingList($this);
            $this->lineItems->add($item);
        }

        return $this;
    }

    /**
     * @param LineItem $item
     *
     * @return $this
     */
    public function removeLineItem(LineItem $item)
    {
        if ($item->getId() === null) {
            if ($this->lineItems->contains($item)) {
                $this->lineItems->removeElement($item);
            }

            return $this;
        }

        foreach ($this->lineItems as $lineItem) {
            if ($item->getId() === $lineItem->getId()) {
                $this->lineItems->removeElement($lineItem);
            }
        }

        return $this;
    }

    /**
     * @return Collection|LineItem[]
     */
    #[\Override]
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param ShoppingListTotal $item
     *
     * @return $this
     */
    public function addTotal(ShoppingListTotal $item)
    {
        $this->totals->set($item->getCurrency(), $item);

        return $this;
    }

    /**
     * @param ShoppingListTotal $item
     *
     * @return $this
     */
    public function removeTotal(ShoppingListTotal $item)
    {
        if ($this->totals->contains($item)) {
            $this->totals->removeElement($item);
        }

        return $this;
    }

    /**
     * @return Collection|ShoppingListTotal[]
     */
    public function getTotals()
    {
        return $this->totals;
    }

    public function getTotalsForCustomerUser(array $currencies = [], ?CustomerUser $customerUser = null): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->andX(
                Criteria::expr()->eq('customerUser', $customerUser),
                Criteria::expr()->in('currency', $currencies)
            )
        );

        return $this->totals->matching($criteria);
    }

    public function getTotalForCustomerUser(string $currency, ?CustomerUser $customerUser = null): ?ShoppingListTotal
    {
        $shoppingListTotal = $this->getTotalsForCustomerUser([$currency], $customerUser)->first();
        if ($shoppingListTotal) {
            return $shoppingListTotal;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        return $this->current;
    }

    /**
     * @param bool $current
     *
     * @return $this
     */
    public function setCurrent($current)
    {
        $this->current = (bool)$current;

        return $this;
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
     * @param Website $website
     *
     * @return $this
     */
    #[\Override]
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
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
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return $this
     */
    #[\Override]
    public function getSourceDocument()
    {
        return $this;
    }

    #[\Override]
    public function getSourceDocumentIdentifier()
    {
        return $this->label;
    }

    /**
     * @return Subtotal
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    public function setSubtotal(Subtotal $subtotal)
    {
        $this->subtotal = $subtotal;
    }

    #[\Override]
    public function getVisitor()
    {
        if ($this->getVisitors()->isEmpty()) {
            return null;
        }

        return $this->getVisitors()->current();
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'is_current' => $this->isCurrent(),
        ];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->lineItems = clone $this->lineItems;
            $this->totals = clone $this->totals;
            $this->cloneExtendEntityStorage();
        }
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }
}
