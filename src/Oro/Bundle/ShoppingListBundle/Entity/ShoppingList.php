<?php

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerAwareTrait;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Shopping List entity
 *
 * @ORM\Table(
 *      name="oro_shopping_list",
 *      indexes={
 *          @ORM\Index(name="oro_shop_lst_created_at_idx", columns={"created_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository")
 * @Config(
 *      routeName="oro_shopping_list_index",
 *      routeView="oro_shopping_list_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-shopping-cart",
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
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="customerUser",
 *              "frontend_owner_column_name"="customer_user_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="shopping"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @method ArrayCollection|CustomerVisitor[] getVisitors()
 */
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

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $notes;

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
     * @var Collection|LineItem[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\ShoppingListBundle\Entity\LineItem",
     *      mappedBy="shoppingList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     **/
    protected $lineItems;

    /**
     * @var bool
     */
    protected $current = false;

    /**
     * @var Collection|ShoppingListTotal[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal",
     *      mappedBy="shoppingList",
     *      cascade={"ALL"},
     *      orphanRemoval=true,
     *      indexBy="currency"
     * )
     **/
    protected $totals;

    /**
     * @var CustomerUser
     *
     * Overrides $customerUser property defined in {@see AuditableFrontendCustomerUserAwareTrait} to disable cascade
     * persist.
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $customerUser;

    /**
     * @var Customer
     *
     * Overrides $customer property defined in {@see AuditableFrontendCustomerAwareTrait} to disable cascade persist.
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer"
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $customer;

    /**
     * @var Subtotal
     */
    protected $subtotal;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        $this->totals = new ArrayCollection();
    }

    /**
     * @return string
     */
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
     * @param Website $website
     *
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
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return $this
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

    /**
     * {@inheritdoc}
     */
    public function getVisitor()
    {
        if ($this->getVisitors()->isEmpty()) {
            return null;
        }

        return $this->getVisitors()->current();
    }

    /**
     * {@inheritdoc}
     */
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
}
