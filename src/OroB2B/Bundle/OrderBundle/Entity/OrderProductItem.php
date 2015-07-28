<?php

namespace OroB2B\Bundle\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * OrderProductItem
 *
 * @ORM\Table(name="orob2b_order_prod_item")
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
class OrderProductItem
{
    const PRICE_TYPE_UNIT       = 10;
    const PRICE_TYPE_BUNDLED    = 20;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var OrderProduct
     *
     * @ORM\ManyToOne(targetEntity="OrderProduct")
     * @ORM\JoinColumn(name="order_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $orderProduct;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", nullable=true)
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
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var int
     *
     * @ORM\Column(name="price_type", type="smallint")
     */
    protected $priceType;

    /**
     * @var bool
     *
     * @ORM\Column(name="from_quote", type="boolean")
     */
    protected $fromQuote;

    /**
     * @var QuoteProductOffer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer")
     * @ORM\JoinColumn(name="quote_product_offer_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProductOffer;

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->value && null !==  $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatePrice()
    {
        $this->value = $this->price ? $this->price->getValue() : null;
        $this->currency = $this->price ? $this->price->getCurrency() : null;
    }

    /**
     * @return array
     */
    public static function getPriceTypes()
    {
        return [
            self::PRICE_TYPE_UNIT       => 'unit',
            self::PRICE_TYPE_BUNDLED    => 'bundled',
        ];
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
     * @return $this
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
     * Set orderProduct
     *
     * @param OrderProduct $orderProduct
     * @return $this
     */
    public function setOrderProduct(OrderProduct $orderProduct = null)
    {
        $this->orderProduct = $orderProduct;

        return $this;
    }

    /**
     * Get orderProduct
     *
     * @return OrderProduct
     */
    public function getOrderProduct()
    {
        return $this->orderProduct;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit $productUnit
     * @return $this
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
     * @return $this
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
     * Set price
     *
     * @param Price $price
     * @return $this
     */
    public function setPrice($price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * Get price
     *
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set priceType
     *
     * @param int $priceType
     * @return OrderProductItem
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * Get priceType
     *
     * @return int
     */
    public function getPriceType()
    {
        return $this->priceType;
    }

    /**
     * Set fromQuote
     *
     * @param bool $fromQuote
     * @return OrderProductItem
     */
    public function setFromQuote($fromQuote)
    {
        $this->fromQuote = $fromQuote;

        return $this;
    }

    /**
     * Get fromQuote
     *
     * @return bool
     */
    public function getFromQuote()
    {
        return $this->fromQuote;
    }
    
    
    /**
     * @param QuoteProductOffer $quoteProductOffer
     * @return OrderProductItem
     */
    public function setQuoteProductOffer(QuoteProductOffer $quoteProductOffer = null)
    {
        $this->quoteProductOffer = $quoteProductOffer;

        return $this;
    }

    /**
     * @return QuoteProductOffer
     */
    public function getQuoteProductOffer()
    {
        return $this->quoteProductOffer;
    }
}
