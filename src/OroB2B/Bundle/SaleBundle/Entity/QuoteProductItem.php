<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

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
    /**
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
        $this->value = $this->price->getValue();
        $this->currency = $this->price->getCurrency();

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
     * @ORM\PostLoad
     */
    public function loadPrice()
    {
        // ToDo: waiting for merge method Price::create
        //$this->price = Price::create($this->value, $this->currency);
        $this->price = new Price();
        $this->price->setValue($this->value)
            ->setCurrency($this->currency)
        ;
    }
}
