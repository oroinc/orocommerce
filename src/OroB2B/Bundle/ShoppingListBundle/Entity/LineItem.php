<?php
namespace OroB2B\Bundle\ShoppingListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Model\ExtendLineItem;

/**
 * @ORM\Table(
 *      name="orob2b_shopping_list_line_item",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_shopping_list_line_item_uidx",
 *              columns={"product_id", "shopping_list_id", "unit_code"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository")
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "entity"={
 *              "icon"="icon-shopping-cart"
 *          }
 *      }
 * )
 */
class LineItem extends ExtendLineItem
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $product;

    /**
     * @var ShoppingList
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList",
     *      cascade={"persist"},
     *      inversedBy="lineItems"
     * )
     * @ORM\JoinColumn(name="shopping_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $shoppingList;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $quantity;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $unit;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product    = $product;

        return $this;
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
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;

        return $this;
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
}
