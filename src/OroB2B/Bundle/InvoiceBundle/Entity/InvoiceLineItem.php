<?php

namespace OroB2B\Bundle\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Model\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\InvoiceBundle\Model\ExtendInvoiceLineItem;
use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

/**
 * @ORM\Table(
 *      name="orob2b_invoice_line_item"
 * )
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
 * @ORM\EntityListeners({ "OroB2B\Bundle\InvoiceBundle\EventListener\ORM\InvoiceListItemEventListener" })
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class InvoiceLineItem extends ExtendInvoiceLineItem implements
    ProductUnitHolderInterface,
    ProductHolderInterface,
    PriceAwareInterface,
    PriceTypeAwareInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255, nullable=true)
     */
    protected $productSku;

    /**
     * @var string
     *
     * @ORM\Column(name="free_form_product", type="string", length=255, nullable=true)
     */
    protected $freeFormProduct;

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
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL", nullable=true)
     */
    protected $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="product_unit_code", type="string", length=255, nullable=true)
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
     * @var Price|null
     */
    protected $price;

    /**
     * @var int
     *
     * @ORM\Column(name="price_type", type="string")
     */
    protected $priceType = PriceTypeAwareInterface::PRICE_TYPE_UNIT;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\InvoiceBundle\Entity\Invoice", inversedBy="lineItems")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $invoice;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * @return string
     */
    public function getFreeFormProduct()
    {
        return $this->freeFormProduct;
    }

    /**
     * @param string $freeFormProduct
     * @return $this
     */
    public function setFreeFormProduct($freeFormProduct)
    {
        $this->freeFormProduct = $freeFormProduct;

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
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    /**
     * @param string $productUnitCode
     * @return $this
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param Invoice $invoice
     * @return $this
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        if (null === $this->price) {
            $this->price = Price::create($this->value, $this->currency);
        }

        return $this->price;
    }

    /**
     * @param Price|null $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;
        $this->updateItemPrice();

        return $this;
    }

    /**
     * @return int
     */
    public function getPriceType()
    {
        return (int) $this->priceType;
    }

    /**
     * @param int $priceType
     * @return $this
     */
    public function setPriceType($priceType)
    {
        $this->priceType = (int) $priceType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    public function updateItemInformation()
    {
        if ($this->getProduct()) {
            $this->productSku = $this->getProduct()->getSku();
        }

        if ($this->getProductUnit()) {
            $this->productUnitCode = $this->getProductUnit()->getCode();
        }

        $this->updateItemPrice();
    }

    /**
     * Splits price object into value and currency fields
     */
    private function updateItemPrice()
    {
        $this->value = is_null($this->price) ? null : $this->price->getValue();
        $this->currency = is_null($this->price) ? null : $this->price->getCurrency();
    }
}
