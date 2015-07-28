<?php

namespace OroB2B\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(name="orob2b_order_product")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class OrderProduct
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="orderProducts")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $order;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255)
     */
    protected $productSku;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var Collection|OrderProductItem[]
     *
     * @ORM\OneToMany(targetEntity="OrderProductItem", mappedBy="orderProduct", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $orderProductItems;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderProductItems   = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order
     *
     * @param Order $order
     * @return OrderProduct
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set product
     *
     * @param Product $product
     * @return OrderProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;
        if ($product) {
            $this->productSku = $product->getSku();
        }

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return OrderProduct
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * Get productSku
     *
     * @return string
     */
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * Set seller comment
     *
     * @param string $comment
     * @return OrderProduct
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get seller comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Add orderProductItem
     *
     * @param OrderProductItem $orderProductItem
     * @return OrderProduct
     */
    public function addOrderProductItem(OrderProductItem $orderProductItem)
    {
        if (!$this->orderProductItems->contains($orderProductItem)) {
            $this->orderProductItems[] = $orderProductItem;
            $orderProductItem->setOrderProduct($this);
        }

        return $this;
    }

    /**
     * Remove orderProductItem
     *
     * @param OrderProductItem $orderProductItem
     * @return OrderProduct
     */
    public function removeOrderProductItem(OrderProductItem $orderProductItem)
    {
        if ($this->orderProductItems->contains($orderProductItem)) {
            $this->orderProductItems->removeElement($orderProductItem);
        }

        return $this;
    }

    /**
     * Get orderProductItems
     *
     * @return Collection|OrderProductItem[]
     */
    public function getOrderProductItems()
    {
        return $this->orderProductItems;
    }
}
