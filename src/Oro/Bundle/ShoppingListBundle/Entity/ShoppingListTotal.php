<?php

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;

/**
 * Entity for a caching shopping list subtotals data by currency
 * If isValid=false values should be recalculated
 *
 * @ORM\Table(
 *     name="oro_shopping_list_total",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_shopping_list_currency_customer_user", columns={
 *              "shopping_list_id",
 *              "currency",
 *              "customer_user_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository")
 **/
class ShoppingListTotal
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ShoppingList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ShoppingListBundle\Entity\ShoppingList", inversedBy="totals")
     * @ORM\JoinColumn(name="shopping_list_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $shoppingList;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255, nullable=false)
     */
    protected $currency;

    /**
     * @var float
     *
     * @ORM\Column(name="subtotal_value", type="money", nullable=true)
     */
    protected $subtotalValue;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_valid", type="boolean")
     */
    protected $valid = false;

    /**
     * @var CustomerUser

     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $customerUser;

    /**
     * @param ShoppingList $shoppingList
     * @param string $currency
     */
    public function __construct(ShoppingList $shoppingList, $currency)
    {
        $this->shoppingList = $shoppingList;
        # By default, the owner is associated with the owner of the shopping list.
        $this->customerUser = $shoppingList->getCustomerUser();
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param boolean $valid
     * @return $this
     */
    public function setValid($valid)
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @param Subtotal $subtotal
     * @return $this
     */
    public function setSubtotal(Subtotal $subtotal)
    {
        if ($subtotal->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException();
        }

        $this->subtotalValue = $subtotal->getAmount();

        return $this;
    }

    /**
     * @return Subtotal
     */
    public function getSubtotal()
    {
        $subtotal = new Subtotal();
        $subtotal->setAmount($this->subtotalValue)
            ->setCurrency($this->currency)
            ->setType(LineItemNotPricedSubtotalProvider::TYPE)
            ->setLabel(LineItemNotPricedSubtotalProvider::LABEL);

        return $subtotal;
    }

    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(?CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
