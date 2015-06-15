<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

/**
 * @ORM\Table(name="orob2b_sale_quote_product_item")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
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
class QuoteProductItem
{
    const STATUS_REQUESTED = 10;
    const STATUS_SUGGESTED_REPLACEMENT = 20;
    const STATUS_ADDITIONAL = 30;
    const STATUS_NOT_AVAILABLE = 40;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var QuoteProduct
     *
     * @ORM\ManyToOne(targetEntity="QuoteProduct", inversedBy="quoteProductItems")
     * @ORM\JoinColumn(name="quote_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProduct;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     */
    protected $quantity;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL")
     */
    protected $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="product_unit_code", type="string", length=255)
     */
    protected $productUnitCode;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money")
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string")
     */
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var float
     *
     * @ORM\Column(name="requested_quantity", type="float")
     */
    protected $requestedQuantity;

    /**
     * @var float
     *
     * @ORM\Column(name="requested_value", type="money")
     */
    protected $requestedValue;

    /**
     * @var string
     *
     * @ORM\Column(name="requested_currency", type="string")
     */
    protected $requestedCurrency;

    /**
     * @var Price
     */
    protected $requestedPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=true)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;


    /**
     * @var RequestProductItem
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem")
     * @ORM\JoinColumn(name="request_product_item_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $requestProductItem;

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->price = Price::create($this->value, $this->currency);
        $this->requestedPrice = Price::create($this->requestedValue, $this->requestedCurrency);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave()
    {
        $this->value = $this->price->getValue();
        $this->currency = $this->price->getCurrency();
        $this->requestedValue = $this->requestedPrice->getValue();
        $this->requestedCurrency = $this->requestedPrice->getCurrency();
    }

    /**
     * Get Statuses Titles array
     *
     * @return array
     */
    public static function getStatusesTitles()
    {
        static $statusTitles = null;
        if (null === $statusTitles) {
            $statusTitles = [
                static::STATUS_REQUESTED => 'orob2b.sale.quoteproductitem.status.requested',
                static::STATUS_SUGGESTED_REPLACEMENT => 'orob2b.sale.quoteproductitem.status.suggested_replacement',
                static::STATUS_ADDITIONAL => 'orob2b.sale.quoteproductitem.status.additional',
                static::STATUS_NOT_AVAILABLE => 'orob2b.sale.quoteproductitem.status.not_available'
            ];
        }

        return $statusTitles;
    }

    /**
     * Get Status Title
     *
     * @return string
     */
    public function getStatusTitle()
    {
        $status = $this->getStatus();
        $titles = static::getStatusesTitles();

        return isset($titles[$status]) ? $titles[$status] : '';
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
     * Set quantity
     *
     * @param float $quantity
     * @return QuoteProductItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set quoteProduct
     *
     * @param QuoteProduct $quoteProduct
     * @return QuoteProductItem
     */
    public function setQuoteProduct(QuoteProduct $quoteProduct = null)
    {
        $this->quoteProduct = $quoteProduct;

        return $this;
    }

    /**
     * Get quoteProduct
     *
     * @return QuoteProduct
     */
    public function getQuoteProduct()
    {
        return $this->quoteProduct;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit $productUnit
     * @return QuoteProductItem
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;
        if ($productUnit) {
            $this->productUnitCode = $productUnit->getCode();
        }

        return $this;
    }

    /**
     * Get productUnit
     *
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set productUnitCode
     *
     * @param string $productUnitCode
     * @return QuoteProductItem
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    /**
     * Get productUnitCode
     *
     * @return ProductUnit
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    /**
     * @param Price $price
     * @return QuoteProductItem
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        $this->preSave();

        return $this;
    }

    /**
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set requestedQuantity
     *
     * @param float $requestedQuantity
     * @return QuoteProductItem
     */
    public function setRequestedQuantity($requestedQuantity)
    {
        $this->requestedQuantity = $requestedQuantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    public function getRequestedQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param Price $requestedPrice
     * @return QuoteProductItem
     */
    public function setRequestedPrice(Price $requestedPrice)
    {
        $this->requestedPrice = $requestedPrice;

        $this->preSave();

        return $this;
    }

    /**
     * @return Price|null
     */
    public function getRequestedPrice()
    {
        return $this->requestedPrice;
    }

    /**
     * @param string $comment
     * @return QuoteProductItem
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param RequestProductItem $requestProductItem
     * @return QuoteProductItem
     */
    public function setRequestProductItem(RequestProductItem $requestProductItem)
    {
        $this->requestProductItem = $requestProductItem;

        return $this;
    }

    /**
     * @return RequestProductItem
     */
    public function getRequestProductItem()
    {
        return $this->requestProductItem;
    }

    /**
     * @param int $status
     * @return QuoteProductItem
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
