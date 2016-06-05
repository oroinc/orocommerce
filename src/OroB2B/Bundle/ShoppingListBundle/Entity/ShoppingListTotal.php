<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;

/**
 * Entity for caching shopping list subtotals data by currency
 * If isValid=false values should be recalculated
 *
 * @ORM\Table(
 *     name="orob2b_shopping_list_total",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_shopping_list_currency", columns={
 *              "shopping_list_id",
 *              "currency"
 *          })
 *      }
 * )
 * @ORM\Entity()
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList", inversedBy="totals")
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
     * @param ShoppingList $shoppingList
     * @param string $currency
     */
    public function __construct(ShoppingList $shoppingList, $currency)
    {
        $this->shoppingList = $shoppingList;
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
}
