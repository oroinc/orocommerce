<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository")
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList")
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
    protected $valid = true;

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
     * @param ShoppingList $shoppingList
     * @return $this
     */
    public function setShoppingList(ShoppingList $shoppingList)
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return float
     */
    public function getSubtotalValue()
    {
        return $this->subtotalValue;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setSubtotalValue($value)
    {
        $this->subtotalValue = $value;

        return $this;
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
}
